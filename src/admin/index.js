import './style.css'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const loader = new GLTFLoader()
const frames = new Map()
const adminI18n = window.wp3dsAdminConfig?.i18n ?? {}

function formatNumber(value) {
  return Number.parseFloat(value).toFixed(3)
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
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
        name: String(part.name || adminI18n.part || 'Part'),
        description: String(part.description || ''),
        characteristics: String(part.characteristics || ''),
        x: Number.parseFloat(part.x || 0) || 0,
        y: Number.parseFloat(part.y || 0) || 0,
        z: Number.parseFloat(part.z || 0) || 0,
      }))
  } catch {
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

function partsStatusLabel(count) {
  if (!count) {
    return adminI18n.noPartsDetected || 'No mesh parts were detected in this GLB file.'
  }

  const template = adminI18n.partsDetected || 'Detected %d parts automatically.'
  return template.replace('%d', String(count))
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
    statusEl.textContent = partsStatusLabel(0)
    syncHiddenField(container, [])
    return
  }

  statusEl.textContent = partsStatusLabel(parts.length)
  listEl.hidden = false

  listEl.innerHTML = `
    <table class="widefat striped wp3ds-parts-table">
      <thead>
        <tr>
          <th>${escapeHtml(adminI18n.partColumn || 'Part')}</th>
          <th>${escapeHtml(adminI18n.descriptionColumn || 'Description')}</th>
          <th>${escapeHtml(adminI18n.characteristicsCol || 'Characteristics')}</th>
          <th>X</th>
          <th>Y</th>
          <th>Z</th>
        </tr>
      </thead>
      <tbody>
        ${parts.map((part, index) => `
          <tr>
            <td>
              <label>
                <span class="screen-reader-text">${escapeHtml(adminI18n.displayName || 'Display name')}</span>
                <input type="text" class="regular-text" data-text-input="name" data-index="${index}" value="${escapeHtml(part.name)}" placeholder="${escapeHtml(adminI18n.displayName || 'Display name')}">
              </label>
              <div class="description">${escapeHtml(part.key)}</div>
            </td>
            <td>
              <textarea rows="3" class="large-text" data-text-input="description" data-index="${index}" placeholder="${escapeHtml(adminI18n.shortSummary || 'Short summary shown in the viewer')}">${escapeHtml(part.description)}</textarea>
            </td>
            <td>
              <textarea rows="3" class="large-text" data-text-input="characteristics" data-index="${index}" placeholder="${escapeHtml(adminI18n.onePerLine || 'One characteristic per line')}">${escapeHtml(part.characteristics)}</textarea>
            </td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="x" data-index="${index}" value="${formatNumber(part.x)}"></td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="y" data-index="${index}" value="${formatNumber(part.y)}"></td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="z" data-index="${index}" value="${formatNumber(part.z)}"></td>
          </tr>
        `).join('')}
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
      statusEl.textContent = adminI18n.selectGlbPrompt || 'Select a GLB file to detect model parts.'
    }
    syncHiddenField(container, storedParts)
    return
  }

  if (statusEl) {
    statusEl.textContent = adminI18n.detectingParts || 'Detecting mesh parts from the GLB file…'
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
        const name = child.name || `${adminI18n.part || 'Part'} ${meshIndex}`
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
          name: storedPart ? storedPart.name : name,
          description: storedPart ? storedPart.description : '',
          characteristics: storedPart ? storedPart.characteristics : '',
          x: storedPart ? storedPart.x : Number.parseFloat(direction.x.toFixed(3)),
          y: storedPart ? storedPart.y : Number.parseFloat(direction.y.toFixed(3)),
          z: storedPart ? storedPart.z : Number.parseFloat(direction.z.toFixed(3)),
        })
      })

      container.dataset.explodeParts = JSON.stringify(detectedParts)
      renderPartsTable(container, detectedParts)
    },
    undefined,
    () => {
      if (listEl) {
        listEl.hidden = true
        listEl.innerHTML = ''
      }
      if (statusEl) {
        statusEl.textContent = adminI18n.loadGlbError || 'Unable to inspect the selected GLB file.'
      }
    }
  )
}

function clearMediaField(button) {
  const input = document.querySelector(button.dataset.clearMedia || '')
  const idInput = document.querySelector(button.dataset.clearMediaId || '')

  if (input) {
    input.value = ''
    input.dispatchEvent(new Event('change', { bubbles: true }))
  }

  if (idInput) {
    idInput.value = ''
  }
}

function isAllowedAttachment(attachment, allowedExtension) {
  if (!allowedExtension) {
    return true
  }
  const url = String(attachment?.url || '').toLowerCase()
  return url.endsWith(`.${allowedExtension.toLowerCase()}`)
}

function initMediaPicker() {
  document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-media-url-input]')
    if (!input) {
      return
    }

    const idInput = document.querySelector(input.dataset.mediaIdTarget || '')
    if (idInput) {
      idInput.value = ''
    }
  })

  document.addEventListener('click', (event) => {
    const clearButton = event.target.closest('[data-clear-media]')
    if (clearButton) {
      event.preventDefault()
      clearMediaField(clearButton)
      return
    }

    const button = event.target.closest('[data-media-target]')
    if (!button) {
      return
    }

    event.preventDefault()
    const targetSelector = button.dataset.mediaTarget
    const input = document.querySelector(targetSelector)
    const idInput = document.querySelector(button.dataset.mediaIdTarget || '')

    if (input && !input.dataset.mediaIdTarget && button.dataset.mediaIdTarget) {
      input.dataset.mediaIdTarget = button.dataset.mediaIdTarget
    }

    if (!targetSelector || !input) {
      return
    }

    const cacheKey = `${targetSelector}:${button.dataset.mediaTitle || ''}`
    if (frames.has(cacheKey)) {
      frames.get(cacheKey).open()
      return
    }

    const frame = wp.media({
      title: button.dataset.mediaTitle || adminI18n.selectFile || 'Select file',
      button: {
        text: button.dataset.mediaButton || adminI18n.useFile || 'Use this file',
      },
      multiple: false,
    })

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON()
      if (!isAllowedAttachment(attachment, button.dataset.allowedExtension)) {
        window.alert(adminI18n.invalidFileType || 'Please select a file with the required extension.')
        return
      }

      input.value = attachment.url || ''
      if (idInput) {
        idInput.value = attachment.id || ''
      }
      input.dispatchEvent(new Event('change', { bubbles: true }))
    })

    frames.set(cacheKey, frame)
    frame.open()
  })
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
    const axisInput = event.target.closest('[data-axis-input]')
    const textInput = event.target.closest('[data-text-input]')
    const parts = normalizeStoredParts(container.querySelector('#wp3ds_explode_parts')?.value || '[]')

    if (axisInput) {
      const partIndex = Number.parseInt(axisInput.dataset.index || '-1', 10)
      const axis = axisInput.dataset.axisInput
      if (!parts[partIndex] || !['x', 'y', 'z'].includes(axis)) {
        return
      }
      parts[partIndex][axis] = Number.parseFloat(axisInput.value || '0') || 0
      container.dataset.explodeParts = JSON.stringify(parts)
      syncHiddenField(container, parts)
      return
    }

    if (!textInput) {
      return
    }

    const partIndex = Number.parseInt(textInput.dataset.index || '-1', 10)
    const field = textInput.dataset.textInput
    if (!parts[partIndex] || !['name', 'description', 'characteristics'].includes(field)) {
      return
    }
    parts[partIndex][field] = textInput.value
    container.dataset.explodeParts = JSON.stringify(parts)
    syncHiddenField(container, parts)
  })

  detectModelParts(modelInput.value.trim(), container)
}

document.addEventListener('DOMContentLoaded', () => {
  initMediaPicker()
  initExplodePartsManager()
})
