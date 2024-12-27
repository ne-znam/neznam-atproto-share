/* global jQuery */
(function ($) {
  /*
  MIT License

  Copyright (c) 2024 Nicholas Sideras

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
  */

  // Public domain image from https://commons.wikimedia.org/wiki/File:Default_pfp.svg
  const defaultAvatar = `<div class="avatar avatar-44 photo"><svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewbox="0 0 340 340">
  <path fill="#DDD" d="m169,.5a169,169 0 1,0 2,0zm0,86a76,76 0 1 1-2,0zM57,287q27-35 67-35h92q40,0 67,35a164,164 0 0,1-226,0"/></svg></div>`

  const rootElement = document.querySelector('#comments.neznam-atproto-share-comments')
  if (!rootElement || !rootElement.dataset.uri) return
  const atProto = rootElement.dataset.uri
  const blockTheme = rootElement.dataset.blockTheme

  fetch(
    'https://public.api.bsky.app/xrpc/app.bsky.feed.getPostThread?uri=' + atProto
  )
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error, status = ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (
        typeof data.thread.replies !== 'undefined' &&
        data.thread.replies.length > 0
      ) {
        const list = renderComments(data.thread, 'comment-list wp-block-comment-template', 1, 1)
        rootElement.replaceChildren(list.ol)
        const someReplies = document.createElement('p')
        someReplies.innerHTML = '<a href="' + ToBskyUrl(rootElement.dataset.uri) + '" class="ugc external nofollow" target="_blank">Post a reply on BlueSky</a>'
        rootElement.append(someReplies)
      } else {
        const noReplies = document.createElement('em')
        noReplies.innerHTML = 'No replies. <a href="' + ToBskyUrl(rootElement.dataset.uri) + '" class="ugc external nofollow" target="_blank">Post a reply on BlueSky</a>'
        rootElement.replaceChildren(noReplies)
      }
    })
    .catch((error) => {
      console.warn(error)
      const p = document.createElement('p')
      p.appendChild(document.createTextNode(`Error: ${error.message}`))
      document.body.appendChild(p, rootElement)
    })

  function ToBskyUrl (uri) {
    const splitUri = uri.split('/')
    if (splitUri[0] === 'at:') {
      return 'https://bsky.app/profile/' + splitUri[2] + '/post/' + splitUri[4]
    } else {
      return uri
    }
  }

  function renderComments (thread, classname, depth, count) {
    if (thread.replies && thread.replies.length > 0) {
      const ol = document.createElement('ol')
      ol.className = classname
      for (const comment of thread.replies) {
        const renderedString = renderComment(comment)
        if (!renderedString) continue
        const htmlContent = createElementFromHTML(renderedString)
        const li = document.createElement('li')
        const swap = count % 2 ? 'odd' : 'even'
        li.className = `comment depth-${depth} ${swap} thread-${swap}`
        li.appendChild(htmlContent)
        const comments = renderComments(comment, 'children', depth + 1, count + 1)
        if (comments) {
          li.appendChild(comments.ol)
          count += comments.count
        }
        count++
        ol.appendChild(li)
      }
      return {
        ol,
        count
      }
    }
    return false
  }

  // https://stackoverflow.com/a/494348
  function createElementFromHTML (htmlString) {
    const div = document.createElement('div')
    div.innerHTML = htmlString.trim()
    return div.firstChild
  }

  function renderComment (comment) {
    if (!comment.post.record || !comment.post.record.text || !comment.post.record.createdAt || !comment.post.author || !comment.post.author.handle || !comment.post.uri) {
      return false
    }

    const replyDate = new Date(comment.post.record.createdAt)
    const authorName = comment.post.author.displayName ? comment.post.author.displayName : '@' + comment.post.author.handle
    const replyCount = comment.post.replyCount ?? '0'
    const repostCount = comment.post.repostCount ?? '0'
    const likeCount = comment.post.likeCount ?? '0'
    let authorImage = defaultAvatar
    if (comment.post.author.avatar) {
      authorImage = `<img src="${comment.post.author.avatar}" class="avatar avatar-44 photo" width="44" height="44">`
    }

    // Icons from https://www.systemuicons.com/
    const replyLinks = `
      <a class="comment-reply-link" href="${ToBskyUrl(comment.post.uri)}" rel="ugc external nofollow" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21" style="stroke:currentColor">
          <path fill="none" stroke-linecap="round" stroke-linejoin="round" d="M11 16.517c4.418 0 8-3.284 8-7.017S15.418 3 11 3S3 6.026 3 9.759c0 1.457.546 2.807 1.475 3.91L3.5 18.25l3.916-2.447a9.2 9.2 0 0 0 3.584.714" />
        </svg>
        ${replyCount}
      </a>
      <span>&nbsp;&nbsp;</span>

      <a class="comment-reply-link" href="${ToBskyUrl(comment.post.uri)}" rel="ugc external nofollow" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21" style="stroke:currentColor">
          <g fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
            <path d="m13.5 13.5l3 3l3-3" /><path d="M9.5 4.5h3a4 4 0 0 1 4 4v8m-9-9l-3-3l-3 3" /><path d="M11.5 16.5h-3a4 4 0 0 1-4-4v-8" />
          </g>
        </svg>
        ${repostCount}
      </a>
      <span>&nbsp;&nbsp;</span>

      <a class="comment-reply-link" href="${ToBskyUrl(comment.post.uri)}" rel="ugc external nofollow" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21" style="stroke:currentColor">
              <path fill="none" stroke-linecap="round" stroke-linejoin="round" d="M10.5 6.5c.5-2.5 4.343-2.657 6-1c1.603 1.603 1.5 4.334 0 6l-6 6l-6-6a4.243 4.243 0 0 1 0-6c1.55-1.55 5.5-1.5 6 1" />
          </svg>
          ${likeCount}
      </a>`

    if (blockTheme) {
      return `<div class="wp-block-group is-layout-flow wp-block-group-is-layout-flow" style="margin-bottom:var(--wp--preset--spacing--40);">
        <div class="wp-block-group is-nowrap is-layout-flex wp-container-core-group-is-layout-11 wp-block-group-is-layout-flex" style="align-items:flex-start;justify-content:flex-start;gap:var(--wp--preset--spacing--20);flex-wrap: nowrap;">
          <div class="wp-block-avatar">${authorImage}</div>
          <div class="wp-block-group is-layout-flow wp-block-group-is-layout-flow">
            <!-- Author name height + comment date height = Avatar height -->
            <div class="wp-block-comment-author-name" style="font-size:18px;margin:2px 0 6px;line-height:100%">
              <a href="https://bsky.app/profile/${comment.post.author.handle}" rel="external nofollow ugc" target="_blank"><b>${authorName}</b></a>
            </div>
            <div class="wp-block-comment-date" style="margin:0;font-size:16px;line-height:100%">
              <small><time datetime="${replyDate}">
                <a href="${ToBskyUrl(comment.post.uri)}" rel="external nofollow ugc" target="_blank">${replyDate.toLocaleString()}</a>
              </time></small>
            </div>
            <div class="wp-block-comment-content"><p>${comment.post.record.text}</p></div>
            <div class="wp-block-comment-reply-link">
              ${replyLinks}
            </div>
          </div>
        </div>
      </div>`
    } else {
      return `<article class="comment comment-body">
      <footer class="comment-meta">
        <div class="comment-author vcard">
          ${authorImage}
          <cite style="font-style: normal">
            <a href="https://bsky.app/profile/${comment.post.author.handle}" rel="external nofollow ugc" target="_blank"><b>${authorName}</b></a>
          </cite>
        </div>
        <div class="comment-metadata">
          <a href="${ToBskyUrl(comment.post.uri)}" rel="external nofollow ugc" class="url" target="_blank">
            <time datetime="${replyDate}">${replyDate.toLocaleString()}</time>
          </a>
        </div>
      </footer>
      <section class="comment-content comment wp-block-comment-content">
        <p>${comment.post.record.text}</p>
      </section>
      <div class="reply is-nowrap">${replyLinks}</div>
      </article>`
    }
  }
})(jQuery)
