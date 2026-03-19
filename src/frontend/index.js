import './style.css'
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'
import { RGBELoader } from 'three/examples/jsm/loaders/RGBELoader.js'

const frontendI18n = window.wp3dsFrontendConfig?.i18n ?? {}

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
    this.selectionGlowIntensity = this.parseOpacityValue(root.dataset.selectionGlowIntensity, 0.22)
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
    this.animationFrameId = 0

    this.isolateMode = false
    this.selected = null
    this.materialStates = new Map()
    this.selectionHighlightMap = new Map()
    this.isolateDimOpacity = this.parseOpacityValue(root.dataset.isolateDimOpacity, 0.18)

    this.partModal = root.querySelector('[data-part-modal]')
    this.partTitleEl = root.querySelector('[data-part-title]')
    this.partDescriptionEl = root.querySelector('[data-part-description]')
    this.partKeyEl = root.querySelector('[data-part-key]')
    this.partCharacteristicsEl = root.querySelector('[data-part-characteristics]')
    this.partCharacteristicsSection = root.querySelector('[data-part-characteristics-section]')

    this.boundResize = () => this.onResize()
    this.boundPointerMove = (event) => this.onPointerMove(event)
    this.boundDoubleClick = (event) => this.onDoubleClick(event)

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
    } catch {
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
      this.setLoadingText(frontendI18n.missingModel || 'No model file is assigned to this viewer.')
      return
    }

    const wrap = this.root.querySelector('.wp3ds-canvas-wrap')
    const width = this.root.clientWidth || 800
    const height = wrap?.clientHeight || 500

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
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2))
    this.renderer.setSize(width, height)

    this.pmremGenerator = new THREE.PMREMGenerator(this.renderer)
    this.pmremGenerator.compileEquirectangularShader()

    this.scene.add(new THREE.AmbientLight(0xffffff, 1.4))

    const directionalLight = new THREE.DirectionalLight(0xffffff, 1.6)
    directionalLight.position.set(4, 8, 4)
    this.scene.add(directionalLight)

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

  setLoadingText(message) {
    if (this.loadingEl) {
      this.loadingEl.textContent = message
    }
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
      () => {}
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
          this.setLoadingText(`${frontendI18n.loadingLabel || 'Loading 3D model…'} ${percent}%`)
        }
      },
      () => {
        this.setLoadingText(frontendI18n.failedModel || 'Failed to load the selected 3D model.')
      }
    )
  }

  collectParts() {
    this.meshParts = []
    let meshIndex = 0

    this.model.traverse((child) => {
      if (!child.isMesh) {
        return
      }

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
    })
  }

  calculateExplodeTargets() {
    if (!this.model) {
      return
    }

    const box = new THREE.Box3().setFromObject(this.model)
    const center = box.getCenter(new THREE.Vector3())
    this.explodeTargets.clear()

    this.meshParts.forEach((mesh) => {
      const original = this.originalPositions.get(mesh.uuid)
      if (!original) {
        return
      }

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
        emissiveIntensity: typeof material.emissiveIntensity === 'number' ? material.emissiveIntensity : null,
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
      if (typeof material.emissiveIntensity === 'number' && state.emissiveIntensity !== null) {
        material.emissiveIntensity = state.emissiveIntensity
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

  removeSelectionHighlight(mesh) {
    const highlight = this.selectionHighlightMap.get(mesh.uuid)
    if (!highlight) {
      return
    }
    mesh.remove(highlight.shell)
    highlight.material.dispose()
    this.selectionHighlightMap.delete(mesh.uuid)
  }

  setSelectionHighlight(mesh, active) {
    this.removeSelectionHighlight(mesh)

    this.forEachMaterial(mesh, (material) => {
      if (material.emissive) {
        material.emissive.setHex(0x000000)
      }
      if (typeof material.emissiveIntensity === 'number') {
        material.emissiveIntensity = 1
      }
      material.needsUpdate = true
    })

    if (!active || this.selectionGlowIntensity <= 0) {
      return
    }

    const material = new THREE.MeshBasicMaterial({
      color: this.selectionHighlightColor,
      side: THREE.BackSide,
      transparent: true,
      opacity: this.selectionGlowIntensity,
      depthTest: true,
      depthWrite: false,
      fog: false,
      toneMapped: false,
    })

    const shell = new THREE.Mesh(mesh.geometry, material)
    shell.name = 'wp3ds-selection-silhouette'
    shell.renderOrder = 9
    shell.scale.setScalar(1.03)
    shell.raycast = () => {}

    mesh.add(shell)
    this.selectionHighlightMap.set(mesh.uuid, { shell, material })
  }

  applyIsolationState() {
    this.meshParts.forEach((mesh) => {
      mesh.visible = true
      this.removeSelectionHighlight(mesh)
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
    this.controls.saveState()
  }

  explode() {
    if (!this.model) {
      return
    }
    this.isExploded = !this.isExploded
    this.updateButtonState('explode', this.isExploded)
  }

  updateExplodeAnimation() {
    if (!this.meshParts.length) {
      return
    }

    this.meshParts.forEach((mesh) => {
      const original = this.originalPositions.get(mesh.uuid)
      const exploded = this.explodeTargets.get(mesh.uuid)
      const target = this.isExploded ? exploded : original
      if (!target) {
        return
      }
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
    }
  }

  hidePartModal() {
    if (this.partModal) {
      this.partModal.hidden = true
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

  getIntersections(event) {
    const rect = this.canvas.getBoundingClientRect()
    this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1
    this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1

    this.raycaster.setFromCamera(this.pointer, this.camera)
    return this.raycaster.intersectObjects(this.meshParts, true)
  }

  onDoubleClick(event) {
    const intersects = this.getIntersections(event)
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
    this.updateButtonState('isolate', false)
    this.isolateMode = false
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
    const intersects = this.getIntersections(event)

    if (this.hovered && (!intersects.length || this.hovered !== intersects[0].object)) {
      this.clearHover(this.hovered)
      this.hovered = null
    }

    if (!intersects.length) {
      return
    }

    const object = intersects[0].object
    if (this.hovered !== object) {
      if (this.hovered) {
        this.clearHover(this.hovered)
      }
      this.hovered = object
      this.applyHover(object)
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
      if (typeof material.emissiveIntensity === 'number') {
        material.emissiveIntensity = 0.35
      }
      material.needsUpdate = true
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
      if (typeof material.emissiveIntensity === 'number') {
        material.emissiveIntensity = 1
      }
      material.needsUpdate = true
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
    this.root.querySelector('[data-action="isolate"]')?.addEventListener('click', () => this.toggleIsolateMode())
    this.root.querySelector('[data-action="reset"]')?.addEventListener('click', () => this.resetView())
    this.root.querySelector('[data-action="autorotate"]')?.addEventListener('click', () => {
      this.controls.autoRotate = !this.controls.autoRotate
      this.updateButtonState('autorotate', this.controls.autoRotate)
    })
    this.root.querySelector('[data-action="explode"]')?.addEventListener('click', () => this.explode())
    this.root.querySelector('[data-action="fullscreen"]')?.addEventListener('click', () => this.toggleFullscreen())
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
    window.addEventListener('resize', this.boundResize)
    this.canvas.addEventListener('pointermove', this.boundPointerMove)
    this.canvas.addEventListener('dblclick', this.boundDoubleClick)
  }

  onResize() {
    if (!this.camera || !this.renderer) {
      return
    }
    const wrap = this.root.querySelector('.wp3ds-canvas-wrap')
    const width = this.root.clientWidth || 800
    const height = wrap?.clientHeight || 500
    this.camera.aspect = width / height
    this.camera.updateProjectionMatrix()
    this.renderer.setSize(width, height)
  }

  hideLoading() {
    if (this.loadingEl) {
      this.loadingEl.style.display = 'none'
    }
  }

  animate() {
    this.animationFrameId = window.requestAnimationFrame(() => this.animate())
    this.updateExplodeAnimation()
    if (this.controls) {
      this.controls.update()
    }
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
