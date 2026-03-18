jQuery(function ($) {
  let mediaFrame

  const input = $('#wp3ds_model_url')

  if (!input.length) return

  if (!$('#wp3ds-open-media').length) {
    input.after(' <button type="button" class="button" id="wp3ds-open-media">Select GLB</button>')
  }

  $(document).on('click', '#wp3ds-open-media', function (e) {
    e.preventDefault()

    if (mediaFrame) {
      mediaFrame.open()
      return
    }

    mediaFrame = wp.media({
      title: 'Select GLB File',
      button: { text: 'Use this file' },
      multiple: false,
    })

    mediaFrame.on('select', function () {
      const attachment = mediaFrame.state().get('selection').first().toJSON()
      input.val(attachment.url)
    })

    mediaFrame.open()
  })
})