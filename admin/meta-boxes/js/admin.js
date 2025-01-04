/* global jQuery */
(function ($, window, document) {
  'use strict'
  const pluginName = 'neznam-atproto-share'
  $(document).ready(function () {
    $(`#${pluginName}-switch-link-post, #${pluginName}-switch-publish-post`).on('click', function (e) {
      e.preventDefault()
      $(`#${pluginName}-publish-mode`).val($(this).data('mode'))
      $(`#${pluginName}-publish-post, #${pluginName}-link-post`).toggle()
    })

    let boxData = neznam_atproto_share_meta_box_object // eslint-disable-line
    const $publishedBox = $(`#${pluginName}-published-post`)
    const $publishBox = $(`#${pluginName}-publish-post`)
    const $linkBox = $(`#${pluginName}-link-post`)

    const processUI = function () {
      $publishedBox.hide()
      $publishBox.hide()
      $linkBox.hide()

      if (boxData.atproto_url) {
        $publishedBox.show()
        $publishedBox.find('a.ext-link').attr('href', boxData.atproto_url)
        return
      }
      $(`#${pluginName}-text-to-publish`).val(boxData.text_to_publish)
      if (boxData.should_publish && boxData.should_publish !== '0') {
        $(`#${pluginName}-should-publish`).prop('checked', true)
      }
      $(`#${pluginName}-publish-post`).show()
      if (boxData.published) {
        $(`#${pluginName}-switch-link-post`).show()
      }
    }
    processUI()

    $publishedBox.find('.update').on('click', function () {
      const $update = $(this)
      $update.prop('disabled', true)
      $publishedBox.find('.spinner').addClass('is-active')
      $.post(boxData.url,
        {
          action: pluginName,
          post_id: jQuery('#post_ID').val(),
          nonce: boxData.nonce,
          subaction: 'disassociate'
        }, function (response) {
          $update.prop('disabled', false)
          $publishedBox.find('.spinner').removeClass('is-active')
          if (!response.success) {
            $(`#${pluginName}-message`).text(response.data.error).show()
            return
          }
          $(`#${pluginName}-message`).hide()
          boxData = response.data
          processUI()
        }
      )
    })

    $(`#${pluginName}-text-to-publish`).on('blur', function () {
      $publishBox.find('.update').data('skip-post', true)
      $publishBox.find('.update').trigger('click')
    })
    $(`#${pluginName}-should-publish`).on('change', function () {
      $publishBox.find('.update').data('skip-post', true)
      $publishBox.find('.update').trigger('click')
    })
    $publishBox.find('.update').on('click', function () {
      const $update = $(this)
      $update.prop('disabled', true)
      $publishBox.find('.spinner').addClass('is-active')
      $.post(boxData.url,
        {
          action: pluginName,
          post_id: jQuery('#post_ID').val(),
          nonce: boxData.nonce,
          subaction: 'publish',
          publish: $(`#${pluginName}-should-publish`).is(':checked') ? 1 : 0,
          text: $(`#${pluginName}-text-to-publish`).val(),
          skip_post: $publishBox.find('.update').data('skip-post') ? 1 : 0
        }, function (response) {
          $update.prop('disabled', false)
          $publishBox.find('.spinner').removeClass('is-active')
          if (!response.success) {
            $(`#${pluginName}-message`).text(response.data.error).show()
            return
          }
          $(`#${pluginName}-message`).hide()
          boxData = response.data
          processUI()
          $publishBox.find('.update').data('skip-post', false)
        }
      )
    })

    $linkBox.find('.update').on('click', function () {
      const $update = $(this)
      $update.prop('disabled', true)
      $linkBox.find('.spinner').addClass('is-active')
      $.post(boxData.url,
        {
          action: pluginName,
          post_id: jQuery('#post_ID').val(),
          nonce: boxData.nonce,
          subaction: 'link',
          rkey: $(`#${pluginName}-published-rkey`).val()
        }, function (response) {
          $update.prop('disabled', false)
          $linkBox.find('.spinner').removeClass('is-active')
          if (!response.success) {
            $(`#${pluginName}-message`).text(response.data.error).show()
            return
          }
          $(`#${pluginName}-message`).hide()
          boxData = response.data
          processUI()
        }
      )
    })
  })
}(jQuery, window, document))
