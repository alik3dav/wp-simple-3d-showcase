import * as THREE from 'three'
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js'

const frames = new Map()
const loader = new GLTFLoader()

function formatNumber(value) {
  return Number.parseFloat(value).toFixed(3)
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('\"', '&quot;')
    .replaceAll("'", '&#039;')
}

function createPartKey(mesh, fallbackIndex) {
  const segments = []
  let node = mesh

  while (node) {
    const label = node.name || node.type || 'Node'
    segments.unshift(label)
    node = node.parent && node.parent.type !== 'Scene' ? node.parent : null
  }

  return `${segments.join(' / ')}#${fallbackIndex}`
}

function normalizeStoredParts(rawValue) {
  if (!rawValue) {
    return []
  }

  try {
    const parsed = JSON.parse(rawValue)

    if (!Array.isArray(parsed)) {
      return []
    }

    return parsed
      .filter((part) => part && part.key)
      .map((part) => ({
        key: String(part.key),
        name: String(part.name || 'Part'),
        x: Number.parseFloat(part.x || 0) || 0,
        y: Number.parseFloat(part.y || 0) || 0,
        z: Number.parseFloat(part.z || 0) || 0,
      }))
  } catch (error) {
    console.error('Failed to parse stored explode parts JSON.', error)
    return []
  }
}

function syncHiddenField(container, parts) {
  const hiddenInput = container.querySelector('#wp3ds_explode_parts')

  if (!hiddenInput) {
    return
  }

  hiddenInput.value = JSON.stringify(parts)
}

function renderPartsTable(container, parts) {
  const listEl = container.querySelector('[data-parts-list]')
  const statusEl = container.querySelector('[data-parts-status]')

  if (!listEl || !statusEl) {
    return
  }

  if (!parts.length) {
    listEl.hidden = true
    listEl.innerHTML = ''
    statusEl.textContent = 'No mesh parts were detected in this GLB file.'
    syncHiddenField(container, [])
    return
  }

  statusEl.textContent = `Detected ${parts.length} part${parts.length === 1 ? '' : 's'} automatically.`
  listEl.hidden = false

  listEl.innerHTML = `
    <table class="widefat striped wp3ds-parts-table">
      <thead>
        <tr>
          <th>Part</th>
          <th>X</th>
          <th>Y</th>
          <th>Z</th>
        </tr>
      </thead>
      <tbody>
        ${parts
          .map(
            (part, index) => `
              <tr>
                <td>
                  <strong>${escapeHtml(part.name)}</strong>
                  <div class="description">${escapeHtml(part.key)}</div>
                </td>
                <td><input type="number" step="0.001" class="small-text" data-axis-input="x" data-index="${index}" value="${formatNumber(part.x)}"></td>
                <td><input type="number" step="0.001" class="small-text" data-axis-input="y" data-index="${index}" value="${formatNumber(part.y)}"></td>
                <td><input type="number" step="0.001" class="small-text" data-axis-input="z" data-index="${index}" value="${formatNumber(part.z)}"></td>
              </tr>
            `
          )
          .join('')}
      </tbody>
    </table>
  `

  syncHiddenField(container, parts)
}

function detectModelParts(url, container) {
  const statusEl = container.querySelector('[data-parts-status]')
  const listEl = container.querySelector('[data-parts-list]')
  const storedParts = normalizeStoredParts(container.dataset.explodeParts || '[]')
  const storedPartMap = new Map(storedParts.map((part) => [part.key, part]))

  if (!url) {
    if (listEl) {
      listEl.hidden = true
      listEl.innerHTML = ''
    }
    if (statusEl) {
      statusEl.textContent = 'Select or enter a GLB file URL to detect model parts.'
    }
    syncHiddenField(container, storedParts)
    return
  }

  if (statusEl) {
    statusEl.textContent = 'Detecting mesh parts from the GLB file…'
  }

  loader.load(
    url,
    (gltf) => {
      const model = gltf.scene
      const box = new THREE.Box3().setFromObject(model)
      const center = box.getCenter(new THREE.Vector3())
      const detectedParts = []
      let meshIndex = 0

      model.traverse((child) => {
        if (!child.isMesh) {
          return
        }

        meshIndex += 1

        const key = createPartKey(child, meshIndex)
        const name = child.name || `Part ${meshIndex}`
        const worldPos = child.getWorldPosition(new THREE.Vector3())
        const direction = worldPos.clone().sub(center)

        if (direction.lengthSq() === 0) {
          direction.set(0, 1, 0)
        } else {
          direction.normalize()
        }

        const storedPart = storedPartMap.get(key)

        detectedParts.push({
          key,
          name,
          x: storedPart ? storedPart.x : Number.parseFloat(direction.x.toFixed(3)),
          y: storedPart ? storedPart.y : Number.parseFloat(direction.y.toFixed(3)),
          z: storedPart ? storedPart.z : Number.parseFloat(direction.z.toFixed(3)),
        })
      })

      container.dataset.explodeParts = JSON.stringify(detectedParts)
      renderPartsTable(container, detectedParts)
    },
    undefined,
    (error) => {
      console.error('Failed to inspect GLB file for explode parts.', error)

      if (listEl) {
        listEl.hidden = true
        listEl.innerHTML = ''
      }

      if (statusEl) {
        statusEl.textContent = 'Unable to load the GLB file for automatic part detection.'
      }
    }
  )
}

function initMediaPicker() {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-media-target]')

    if (!button) {
      return
    }

    event.preventDefault()

    const targetSelector = button.dataset.mediaTarget
    const input = document.querySelector(targetSelector)

    if (!targetSelector || !input) {
      return
    }

    const cacheKey = `${targetSelector}:${button.dataset.mediaTitle || ''}`

    if (frames.has(cacheKey)) {
      frames.get(cacheKey).open()
      return
    }

    const frame = wp.media({
      title: button.dataset.mediaTitle || 'Select file',
      button: {
        text: button.dataset.mediaButton || 'Use this file',
      },
      multiple: false,
    })

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON()
      input.value = attachment.url
      input.dispatchEvent(new Event('change', { bubbles: true }))
    })

    frames.set(cacheKey, frame)
    frame.open()
  })

  const modelInput = document.querySelector('#wp3ds_model_url')

  if (modelInput && !document.querySelector('#wp3ds-open-media')) {
    modelInput.insertAdjacentHTML(
      'afterend',
      ' <button type="button" class="button" id="wp3ds-open-media" data-media-target="#wp3ds_model_url" data-media-title="Select GLB File" data-media-button="Use this file">Select GLB</button>'
    )
  }
}

function initExplodePartsManager() {
  const container = document.querySelector('.wp3ds-explode-parts')
  const modelInput = document.querySelector('#wp3ds_model_url')

  if (!container || !modelInput) {
    return
  }

  modelInput.addEventListener('change', () => {
    detectModelParts(modelInput.value.trim(), container)
  })

  container.addEventListener('input', (event) => {
    const input = event.target.closest('[data-axis-input]')

    if (!input) {
      return
    }

    const parts = normalizeStoredParts(container.querySelector('#wp3ds_explode_parts')?.value || '[]')
    const partIndex = Number.parseInt(input.dataset.index || '-1', 10)
    const axis = input.dataset.axisInput

    if (!parts[partIndex] || !['x', 'y', 'z'].includes(axis)) {
      return
    }

    parts[partIndex][axis] = Number.parseFloat(input.value || '0') || 0
    container.dataset.explodeParts = JSON.stringify(parts)
    syncHiddenField(container, parts)
  })

  detectModelParts(modelInput.value.trim(), container)
}

document.addEventListener('DOMContentLoaded', () => {
  initMediaPicker()
  initExplodePartsManager()
})
