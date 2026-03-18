jQuery(function ($) {
  const frames = new Map()

  $(document).on('click', '[data-media-target]', function (e) {
    e.preventDefault()

    const button = $(this)
    const targetSelector = button.data('media-target')
    const input = $(targetSelector)

    if (!input.length) {
      return
    }

    const cacheKey = `${targetSelector}:${button.data('media-title') || ''}`

    if (frames.has(cacheKey)) {
      frames.get(cacheKey).open()
      return
    }

    const frame = wp.media({
      title: button.data('media-title') || 'Select file',
      button: {
        text: button.data('media-button') || 'Use this file',
      },
      multiple: false,
    })

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON()
      input.val(attachment.url).trigger('change')
    })

    frames.set(cacheKey, frame)
    frame.open()
  })

  if ($('#wp3ds_model_url').length && !$('#wp3ds-open-media').length) {
    $('#wp3ds_model_url').after(
      ' <button type="button" class="button" id="wp3ds-open-media" data-media-target="#wp3ds_model_url" data-media-title="Select GLB File" data-media-button="Use this file">Select GLB</button>'
    )
  }
})
