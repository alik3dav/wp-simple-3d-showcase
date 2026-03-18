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

    this.init()
  }

  init() {
    console.log('WP3DS viewer init', {
      modelUrl: this.modelUrl,
      bgColor: this.bgColor,
      autoRotate: this.autoRotate,
      explodeStep: this.explodeStep,
      hdriMapUrl: this.hdriMapUrl,
    })

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

        console.log('GLB model loaded successfully:', this.modelUrl)
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

    this.model.traverse((child) => {
      if (child.isMesh) {
        this.meshParts.push(child)
        this.originalPositions.set(child.uuid, child.position.clone())

        if (child.material) {
          child.material = child.material.clone()

          if (this.environmentMap) {
            child.material.envMap = this.environmentMap
            child.material.needsUpdate = true
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

      const dir = worldPos.clone().sub(center)

      if (dir.lengthSq() === 0) {
        dir.set(0, 1, 0)
      } else {
        dir.normalize()
      }

      this.explodeTargets.set(mesh.uuid, original.clone().add(dir.multiplyScalar(this.explodeStep)))
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

    if (!this.isolateMode) {
      this.meshParts.forEach((mesh) => {
        mesh.visible = true
      })
      this.selected = null
    }
  }

  onClick(event) {
    const rect = this.canvas.getBoundingClientRect()
    this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1
    this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1

    this.raycaster.setFromCamera(this.pointer, this.camera)
    const intersects = this.raycaster.intersectObjects(this.meshParts, true)

    if (!intersects.length) return

    const obj = intersects[0].object

    if (this.isolateMode) {
      this.selected = obj

      this.meshParts.forEach((mesh) => {
        mesh.visible = mesh === obj
      })

      this.focusObject(obj)
    }
  }

  focusObject(obj) {
    const box = new THREE.Box3().setFromObject(obj)
    const center = box.getCenter(new THREE.Vector3())
    const size = box.getSize(new THREE.Vector3())

    const maxDim = Math.max(size.x, size.y, size.z)
    const distance = Math.max(maxDim * 2, 1)

    this.camera.position.copy(center.clone().add(new THREE.Vector3(0, 0, distance)))
    this.controls.target.copy(center)
    this.controls.update()
  }

  resetView() {
    this.controls.reset()
    this.isExploded = false
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
    if (mesh.material && mesh.material.emissive) {
      mesh.material.emissive.setHex(0x333333)
    }
  }

  clearHover(mesh) {
    if (mesh.material && mesh.material.emissive) {
      mesh.material.emissive.setHex(0x000000)
    }
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
    })

    this.root.querySelector('[data-action="explode"]')?.addEventListener('click', () => {
      this.explode()
    })

    this.root.querySelector('[data-action="fullscreen"]')?.addEventListener('click', () => {
      this.toggleFullscreen()
    })
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
    this.canvas.addEventListener('click', (e) => this.onClick(e))
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
