import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js'
import { RGBELoader } from 'three/addons/loaders/RGBELoader.js'

class WP3DSViewer {
  constructor(root) {
    this.root = root
    this.canvas = root.querySelector('canvas')
    this.loadingEl = root.querySelector('.wp3ds-loading')
    this.modelUrl = root.dataset.modelUrl
    this.bgColor = root.dataset.bgColor || '#f5f5f5'
    this.autoRotate = root.dataset.autoRotate === 'true'
    this.explodeStep = parseFloat(root.dataset.explodeStep || '0.15')
    this.hdriMapUrl = root.dataset.hdriMapUrl || ''
    this.selectionHighlightColor = this.parseColorValue(root.dataset.selectionHighlightColor, 0x2f6df6)
    this.hoverHighlightColor = this.parseColorValue(root.dataset.hoverHighlightColor, 0x333333)
    this.explodePartsSettings = this.parseExplodeParts(root.dataset.explodeParts || '[]')

    this.scene = null
    this.camera = null
    this.renderer = null
    this.controls = null
    this.model = null
    this.meshParts = []
    this.originalPositions = new Map()
    this.explodeTargets = new Map()
    this.explodeLerpAlpha = 0.12
    this.isExploded = false
    this.raycaster = new THREE.Raycaster()
    this.pointer = new THREE.Vector2()
    this.hovered = null
    this.pmremGenerator = null
    this.environmentMap = null

    this.isolateMode = false
    this.selected = null
    this.materialStates = new Map()
    this.isolateDimOpacity = this.parseOpacityValue(root.dataset.isolateDimOpacity, 0.18)

    this.partModal = root.querySelector('[data-part-modal]')
    this.partTitleEl = root.querySelector('[data-part-title]')
    this.partDescriptionEl = root.querySelector('[data-part-description]')
    this.partKeyEl = root.querySelector('[data-part-key]')
    this.partCharacteristicsEl = root.querySelector('[data-part-characteristics]')
    this.partCharacteristicsSection = root.querySelector('[data-part-characteristics-section]')

    this.init()
  }

  parseExplodeParts(rawValue) {
    try {
      const parsed = JSON.parse(rawValue)

      if (!Array.isArray(parsed)) {
        return new Map()
      }

      return new Map(
        parsed
          .filter((part) => part && part.key)
          .map((part) => [
            String(part.key),
            {
              key: String(part.key),
              name: String(part.name || 'Part'),
              description: String(part.description || ''),
              characteristics: String(part.characteristics || ''),
              x: Number.parseFloat(part.x || 0) || 0,
              y: Number.parseFloat(part.y || 0) || 0,
              z: Number.parseFloat(part.z || 0) || 0,
            },
          ])
      )
    } catch (error) {
      console.error('Failed to parse explode part settings.', error)
      return new Map()
    }
  }

  createPartKey(mesh, fallbackIndex) {
    const segments = []
    let node = mesh

    while (node) {
      const label = node.name || node.type || 'Node'
      segments.unshift(label)
      node = node.parent && node.parent.type !== 'Scene' ? node.parent : null
    }

    return `${segments.join(' / ')}#${fallbackIndex}`
  }

  parseColorValue(value, fallback) {
    if (typeof value !== 'string') {
      return fallback
    }

    const normalized = value.trim().replace(/^#/, '')

    if (!/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(normalized)) {
      return fallback
    }

    const expanded = normalized.length === 3
      ? normalized.split('').map((char) => `${char}${char}`).join('')
      : normalized

    return Number.parseInt(expanded, 16)
  }

  parseOpacityValue(value, fallback) {
    const parsed = Number.parseFloat(value || '')

    if (Number.isNaN(parsed)) {
      return fallback
    }

    return Math.min(Math.max(parsed, 0), 1)
  }

  init() {
    if (!this.modelUrl) {
      console.error('No model URL found')
      if (this.loadingEl) {
        this.loadingEl.textContent = 'No model URL found'
      }
      return
    }

    const width = this.root.clientWidth || 800
    const wrap = this.root.querySelector('.wp3ds-canvas-wrap')
    const height = wrap.clientHeight || 500

    this.scene = new THREE.Scene()
    this.scene.background = new THREE.Color(this.bgColor)

    this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 2000)
    this.camera.position.set(0, 1.5, 4)

    this.renderer = new THREE.WebGLRenderer({
      canvas: this.canvas,
      antialias: true,
      alpha: false,
    })
    this.renderer.outputColorSpace = THREE.SRGBColorSpace
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping
    this.renderer.toneMappingExposure = 1
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
    this.renderer.setSize(width, height)

    this.pmremGenerator = new THREE.PMREMGenerator(this.renderer)
    this.pmremGenerator.compileEquirectangularShader()

    const ambient = new THREE.AmbientLight(0xffffff, 1.4)
    this.scene.add(ambient)

    const dir = new THREE.DirectionalLight(0xffffff, 1.6)
    dir.position.set(4, 8, 4)
    this.scene.add(dir)

    this.controls = new OrbitControls(this.camera, this.canvas)
    this.controls.enableDamping = true
    this.controls.autoRotate = this.autoRotate
    this.controls.autoRotateSpeed = 1.2

    this.loadEnvironmentMap()
    this.loadModel()
    this.bindUI()
    this.bindEvents()
    this.animate()
  }

  loadEnvironmentMap() {
    if (!this.hdriMapUrl || !this.scene || !this.pmremGenerator) {
      return
    }

    const rgbeLoader = new RGBELoader()

    rgbeLoader.load(
      this.hdriMapUrl,
      (texture) => {
        const envTexture = this.pmremGenerator.fromEquirectangular(texture).texture

        texture.dispose()

        this.environmentMap = envTexture
        this.scene.environment = envTexture
      },
      undefined,
      (error) => {
        console.error('Failed to load HDRI map:', error)
      }
    )
  }

  loadModel() {
    const loader = new GLTFLoader()

    loader.load(
      this.modelUrl,
      (gltf) => {
        this.model = gltf.scene
        this.scene.add(this.model)

        this.collectParts()
        this.centerAndFitModel()
        this.calculateExplodeTargets()
        this.hideLoading()
      },
      (progress) => {
        if (progress.total) {
          const percent = Math.round((progress.loaded / progress.total) * 100)
          if (this.loadingEl) {
            this.loadingEl.textContent = `Loading 3D model… ${percent}%`
          }
        }
      },
      (error) => {
        console.error('Failed to load GLB model:', error)
        if (this.loadingEl) {
          this.loadingEl.textContent = 'Failed to load model'
        }
      }
    )
  }

  collectParts() {
    this.meshParts = []
    let meshIndex = 0

    this.model.traverse((child) => {
      if (child.isMesh) {
        meshIndex += 1

        child.userData.wp3dsPartKey = this.createPartKey(child, meshIndex)
        child.userData.wp3dsPartMeta = this.explodePartsSettings.get(child.userData.wp3dsPartKey) || null
        this.meshParts.push(child)
        this.originalPositions.set(child.uuid, child.position.clone())

        if (child.material) {
          child.material = Array.isArray(child.material)
            ? child.material.map((material) => material.clone())
            : child.material.clone()

          this.storeMaterialState(child)

          if (this.environmentMap) {
            this.forEachMaterial(child, (material) => {
              material.envMap = this.environmentMap
              material.needsUpdate = true
            })
          }
        }
      }
    })
  }

  calculateExplodeTargets() {
    if (!this.model) return

    const box = new THREE.Box3().setFromObject(this.model)
    const center = box.getCenter(new THREE.Vector3())

    this.explodeTargets.clear()

    this.meshParts.forEach((mesh) => {
      const original = this.originalPositions.get(mesh.uuid)
      if (!original) return

      const worldPos = new THREE.Vector3()
      mesh.getWorldPosition(worldPos)

      const defaultDirection = worldPos.clone().sub(center)

      if (defaultDirection.lengthSq() === 0) {
        defaultDirection.set(0, 1, 0)
      } else {
        defaultDirection.normalize()
      }

      const configuredDirection = this.explodePartsSettings.get(mesh.userData.wp3dsPartKey)
      const direction = configuredDirection
        ? new THREE.Vector3(configuredDirection.x, configuredDirection.y, configuredDirection.z)
        : defaultDirection

      this.explodeTargets.set(mesh.uuid, original.clone().add(direction.multiplyScalar(this.explodeStep)))
    })
  }

  forEachMaterial(mesh, callback) {
    const materials = Array.isArray(mesh.material) ? mesh.material : [mesh.material]

    materials.filter(Boolean).forEach(callback)
  }

  storeMaterialState(mesh) {
    const states = []

    this.forEachMaterial(mesh, (material) => {
      states.push({
        opacity: material.opacity,
        transparent: material.transparent,
        depthWrite: material.depthWrite,
        emissive: material.emissive ? material.emissive.getHex() : null,
      })
    })

    this.materialStates.set(mesh.uuid, states)
  }

  restoreMaterialState(mesh) {
    const states = this.materialStates.get(mesh.uuid)

    if (!states) {
      return
    }

    let index = 0

    this.forEachMaterial(mesh, (material) => {
      const state = states[index]
      index += 1

      if (!state) {
        return
      }

      material.opacity = state.opacity
      material.transparent = state.transparent
      material.depthWrite = state.depthWrite
      if (material.emissive && state.emissive !== null) {
        material.emissive.setHex(state.emissive)
      }
      material.needsUpdate = true
    })
  }

  setMeshOpacity(mesh, opacity) {
    this.forEachMaterial(mesh, (material) => {
      material.opacity = opacity
      material.transparent = opacity < 1 || material.transparent
      material.depthWrite = opacity >= 1
      material.needsUpdate = true
    })
  }

  setSelectionHighlight(mesh, active) {
    this.forEachMaterial(mesh, (material) => {
      if (material.emissive) {
        material.emissive.setHex(active ? this.selectionHighlightColor : 0x000000)
      }
      material.needsUpdate = true
    })
  }

  applyIsolationState() {
    this.meshParts.forEach((mesh) => {
      mesh.visible = true
      this.restoreMaterialState(mesh)

      if (mesh === this.selected) {
        this.setSelectionHighlight(mesh, true)
        return
      }

      if (this.isolateMode && this.selected) {
        this.setMeshOpacity(mesh, this.isolateDimOpacity)
      }
    })
  }

  centerAndFitModel() {
    const box = new THREE.Box3().setFromObject(this.model)
    const center = box.getCenter(new THREE.Vector3())
    const size = box.getSize(new THREE.Vector3())

    this.model.position.sub(center)

    const maxDim = Math.max(size.x, size.y, size.z)
    const fitDistance = Math.max(maxDim * 1.8, 2)

    this.camera.position.set(0, Math.max(maxDim * 0.4, 0.5), fitDistance)
    this.controls.target.set(0, 0, 0)
    this.controls.update()
  }

  explode() {
    if (!this.model) return

    this.isExploded = !this.isExploded
    this.updateButtonState('explode', this.isExploded)
  }

  updateExplodeAnimation() {
    if (!this.meshParts.length) return

    this.meshParts.forEach((mesh) => {
      const original = this.originalPositions.get(mesh.uuid)
      const exploded = this.explodeTargets.get(mesh.uuid)
      const target = this.isExploded ? exploded : original

      if (!target) return

      mesh.position.lerp(target, this.explodeLerpAlpha)

      if (mesh.position.distanceToSquared(target) < 0.000001) {
        mesh.position.copy(target)
      }
    })
  }

  toggleIsolateMode() {
    this.isolateMode = !this.isolateMode

    if (!this.isolateMode && !this.partModal?.hidden && this.selected) {
      this.applyIsolationState()
    }

    this.updateButtonState('isolate', this.isolateMode)
    this.applyIsolationState()
  }

  getPartMeta(mesh) {
    const storedMeta = mesh.userData.wp3dsPartMeta || {}
    const name = storedMeta.name || mesh.name || 'Part'
    const description = storedMeta.description || 'No additional part details have been added yet.'
    const characteristics = String(storedMeta.characteristics || '')
      .split(/\r?\n|,/)
      .map((item) => item.trim())
      .filter(Boolean)

    return {
      key: mesh.userData.wp3dsPartKey || mesh.name || 'Part',
      name,
      description,
      characteristics,
    }
  }

  showPartModal(mesh) {
    const meta = this.getPartMeta(mesh)

    if (this.partTitleEl) {
      this.partTitleEl.textContent = meta.name
    }

    if (this.partDescriptionEl) {
      this.partDescriptionEl.textContent = meta.description
    }

    if (this.partKeyEl) {
      this.partKeyEl.textContent = meta.key
    }

    if (this.partCharacteristicsEl && this.partCharacteristicsSection) {
      if (meta.characteristics.length) {
        this.partCharacteristicsEl.innerHTML = meta.characteristics
          .map((item) => `<li>${this.escapeHtml(item)}</li>`)
          .join('')
        this.partCharacteristicsSection.hidden = false
      } else {
        this.partCharacteristicsEl.innerHTML = ''
        this.partCharacteristicsSection.hidden = true
      }
    }

    if (this.partModal) {
      this.partModal.hidden = false
      this.partModal.classList.add('is-visible')
    }
  }

  hidePartModal() {
    if (this.partModal) {
      this.partModal.hidden = true
      this.partModal.classList.remove('is-visible')
    }
  }

  toggleSelectedPart(mesh) {
    if (this.selected === mesh) {
      this.selected = null
      this.hidePartModal()
      this.applyIsolationState()
      return
    }

    this.selected = mesh
    this.showPartModal(mesh)
    this.applyIsolationState()
  }

  onDoubleClick(event) {
    const rect = this.canvas.getBoundingClientRect()
    this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1
    this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1

    this.raycaster.setFromCamera(this.pointer, this.camera)
    const intersects = this.raycaster.intersectObjects(this.meshParts, true)

    if (!intersects.length) {
      if (!this.isolateMode) {
        this.selected = null
        this.hidePartModal()
        this.applyIsolationState()
      }
      return
    }

    this.toggleSelectedPart(intersects[0].object)
  }

  resetView() {
    this.controls.reset()
    this.isExploded = false
    this.selected = null
    this.hidePartModal()
    this.updateButtonState('explode', false)
    this.applyIsolationState()
  }

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      this.root.requestFullscreen?.()
    } else {
      document.exitFullscreen?.()
    }
  }

  onPointerMove(event) {
    const rect = this.canvas.getBoundingClientRect()
    this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1
    this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1

    this.raycaster.setFromCamera(this.pointer, this.camera)
    const intersects = this.raycaster.intersectObjects(this.meshParts, true)

    if (this.hovered && (!intersects.length || this.hovered !== intersects[0].object)) {
      this.clearHover(this.hovered)
      this.hovered = null
    }

    if (intersects.length) {
      const obj = intersects[0].object
      if (this.hovered !== obj) {
        if (this.hovered) this.clearHover(this.hovered)
        this.hovered = obj
        this.applyHover(obj)
      }
    }
  }

  applyHover(mesh) {
    if (mesh === this.selected) {
      return
    }

    this.forEachMaterial(mesh, (material) => {
      if (material.emissive) {
        material.emissive.setHex(this.hoverHighlightColor)
      }
    })
  }

  clearHover(mesh) {
    if (mesh === this.selected) {
      this.setSelectionHighlight(mesh, true)
      return
    }

    this.forEachMaterial(mesh, (material) => {
      if (material.emissive) {
        material.emissive.setHex(0x000000)
      }
    })
  }

  updateButtonState(action, active) {
    const button = this.root.querySelector(`[data-action="${action}"]`)

    if (!button) {
      return
    }

    button.classList.toggle('is-active', active)
    button.setAttribute('aria-pressed', active ? 'true' : 'false')
  }

  escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;')
  }

  bindUI() {
    this.root.querySelector('[data-action="isolate"]')?.addEventListener('click', () => {
      this.toggleIsolateMode()
    })

    this.root.querySelector('[data-action="reset"]')?.addEventListener('click', () => {
      this.resetView()
    })

    this.root.querySelector('[data-action="autorotate"]')?.addEventListener('click', () => {
      this.controls.autoRotate = !this.controls.autoRotate
      this.updateButtonState('autorotate', this.controls.autoRotate)
    })

    this.root.querySelector('[data-action="explode"]')?.addEventListener('click', () => {
      this.explode()
    })

    this.root.querySelector('[data-action="fullscreen"]')?.addEventListener('click', () => {
      this.toggleFullscreen()
    })

    this.root.querySelector('[data-action="close-part-modal"]')?.addEventListener('click', () => {
      this.selected = null
      this.hidePartModal()
      this.applyIsolationState()
    })

    this.updateButtonState('autorotate', this.controls.autoRotate)
    this.updateButtonState('explode', this.isExploded)
    this.updateButtonState('isolate', this.isolateMode)
  }

  bindEvents() {
    window.addEventListener('resize', () => {
      const width = this.root.clientWidth || 800
      const wrap = this.root.querySelector('.wp3ds-canvas-wrap')
      const height = wrap.clientHeight || 500

      this.camera.aspect = width / height
      this.camera.updateProjectionMatrix()
      this.renderer.setSize(width, height)
    })

    this.canvas.addEventListener('pointermove', (e) => this.onPointerMove(e))
    this.canvas.addEventListener('dblclick', (e) => this.onDoubleClick(e))
  }

  hideLoading() {
    if (this.loadingEl) {
      this.loadingEl.style.display = 'none'
    }
  }

  animate() {
    requestAnimationFrame(() => this.animate())
    this.updateExplodeAnimation()
    if (this.controls) this.controls.update()
    if (this.renderer && this.scene && this.camera) {
      this.renderer.render(this.scene, this.camera)
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.wp3ds-viewer').forEach((root) => {
    new WP3DSViewer(root)
  })
})
