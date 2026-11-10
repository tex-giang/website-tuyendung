(function () {
  'use strict';

  const source = document.getElementById('post-body');
  const target = document.getElementById('post-rich-editor');
  if (!source || !target || typeof window.Quill !== 'function') return;

  const form = source.form;
  const rich = form.querySelector('[data-post-rich]');
  const sourceButton = form.querySelector('[data-post-source]');
  const fileInput = form.querySelector('[data-post-inline-image]');
  const status = form.querySelector('[data-post-editor-status]');
  const save = Array.from(form.elements).find(element => element.type === 'submit');
  let selection;
  let uploading = false;

  const announce = (message) => {
    status.textContent = message;
    status.hidden = !message;
  };

  async function uploadImage(range, files) {
    if (uploading || !files.length) return;
    uploading = true;
    sourceButton.disabled = true;
    if (save) save.disabled = true;
    announce('Đang tải ảnh vào bài viết…');
    try {
      let index = range?.index ?? editor.getLength() - 1;
      for (const file of files) {
        const body = new FormData();
        body.append('upload', file);
        body.append('token', target.dataset.uploadToken);
        const response = await fetch(target.dataset.uploadUrl, { method: 'POST', credentials: 'same-origin', body });
        const result = await response.json();
        if (!response.ok || result.uploaded !== 1 || typeof result.url !== 'string') {
          throw new Error(result.error?.message || 'Không tải được ảnh.');
        }
        const url = new URL(result.url, location.origin);
        if (url.origin !== location.origin) throw new Error('Địa chỉ ảnh tải lên không hợp lệ.');
        editor.insertEmbed(index, 'image', result.url, 'user');
        index += 1;
      }
      editor.setSelection(index, 0);
      announce('');
    } catch (error) {
      announce(error.message || 'Không tải được ảnh.');
    } finally {
      uploading = false;
      sourceButton.disabled = false;
      if (save) save.disabled = false;
    }
  }

  const editor = new Quill(target, {
    theme: 'snow',
    placeholder: 'Viết nội dung bài viết…',
    modules: {
      toolbar: {
        container: [[{ header: [2, 3, 4, false] }], ['bold', 'italic', 'underline', 'strike'],
          [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link', 'image'], ['clean']],
        handlers: {
          image() {
            selection = editor.getSelection(true);
            fileInput.click();
          },
        },
      },
      uploader: {
        mimetypes: ['image/jpeg', 'image/png', 'image/webp'],
        handler: uploadImage,
      },
    },
  });

  editor.root.setAttribute('aria-labelledby', 'post-body-label');
  editor.root.setAttribute('aria-multiline', 'true');
  editor.root.setAttribute('role', 'textbox');

  function showSource() {
    rich.hidden = true;
    source.hidden = false;
    sourceButton.setAttribute('aria-pressed', 'true');
  }

  function showRich() {
    if (/<(?:table|figure|figcaption|pre|code|div|hr|sup|sub)\b|\s(?:class|style)=/i.test(source.value)) {
      showSource();
      announce('Bài có định dạng HTML cần giữ nguyên. Bạn có thể chỉnh trực tiếp ở chế độ mã HTML.');
      return;
    }
    rich.hidden = false;
    source.hidden = true;
    sourceButton.setAttribute('aria-pressed', 'false');
    editor.setContents(editor.clipboard.convert({ html: source.value }), 'silent');
    announce('');
  }

  // Keep the original HTML until the author edits the rich text.
  editor.on('text-change', () => {
    source.value = editor.getLength() > 1 ? editor.getSemanticHTML().replaceAll('&nbsp;', ' ') : '';
  });
  sourceButton.addEventListener('click', () => {
    if (rich.hidden) showRich();
    else {
      showSource();
      source.focus();
    }
  });
  fileInput.addEventListener('change', () => {
    const files = Array.from(fileInput.files);
    fileInput.value = '';
    void uploadImage(selection, files);
  });
  source.required = false;
  sourceButton.hidden = false;
  showRich();
})();
