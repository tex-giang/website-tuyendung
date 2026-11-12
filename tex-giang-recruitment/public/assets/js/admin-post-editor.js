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

  // ---------------------------------------------------------------------
  // Dán ảnh từ Word / Google Docs: trình duyệt đưa ảnh dưới 3 dạng khác nhau.
  //  1. clipboardData.files/items -> file ảnh thật  -> upload thẳng.
  //  2. <img src="data:image/...;base64,...">       -> giải mã -> upload.
  //  3. <img src="file:///C:/...">                  -> KHÔNG đọc được; ảnh nằm
  //     trong RTF dạng \pngblip/\jpegblip hex      -> bóc hex -> upload.
  // Chặn paste trước Quill để ảnh không bị biến thành thẻ <img> gãy (//:0).
  // ---------------------------------------------------------------------
  const Delta = window.Quill.import('delta');

  function hexToFile(hex, mime, index) {
    if (!/^[0-9a-fA-F]+$/.test(hex) || hex.length < 100 || hex.length % 2 !== 0) return null;
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < bytes.length; i += 1) {
      bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    const ext = mime === 'image/jpeg' ? 'jpg' : 'png';
    return new File([bytes], 'word-' + Date.now() + '-' + index + '.' + ext, { type: mime });
  }

  /** Bóc ảnh nhúng trong RTF (\pngblip / \jpegblip). */
  function extractRtfImages(rtf) {
    const found = [];
    let cursor = 0;
    while (true) {
      const pict = rtf.indexOf('\\pict', cursor);
      if (pict < 0) break;
      const open = rtf.lastIndexOf('{', pict);
      if (open < 0) break;
      let depth = 0;
      let end = -1;
      for (let i = open; i < rtf.length; i += 1) {
        const ch = rtf[i];
        if (ch === '{') depth += 1;
        else if (ch === '}') {
          depth -= 1;
          if (depth === 0) { end = i; break; }
        }
      }
      if (end < 0) break;
      const group = rtf.slice(open, end + 1);
      const mime = /\\pngblip/.test(group) ? 'image/png' : (/\\jpegblip/.test(group) ? 'image/jpeg' : '');
      if (mime !== '') {
        const hex = group
          .replace(/^\{\\pict/, '')
          .replace(/\\[a-z]+-?\d*\s*/gi, '')
          .replace(/[{}]/g, '')
          .replace(/[^0-9a-fA-F]/g, '');
        const file = hexToFile(hex, mime, found.length);
        if (file) found.push(file);
      }
      cursor = end + 1;
    }
    return found;
  }

  function dataUriToFile(uri, index) {
    const match = /^data:(image\/(?:png|jpe?g|webp));base64,([a-z0-9+/=\s]+)$/i.exec(uri);
    if (!match) return null;
    const mime = match[1].toLowerCase().replace('image/jpg', 'image/jpeg');
    let binary;
    try {
      binary = window.atob(match[2].replace(/\s+/g, ''));
    } catch (error) {
      return null;
    }
    if (binary.length < 50) return null;
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i);
    }
    const ext = mime === 'image/jpeg' ? 'jpg' : mime.replace('image/', '');
    return new File([bytes], 'pasted-' + Date.now() + '-' + index + '.' + ext, { type: mime });
  }

  /**
   * Dán ảnh từ Word/Docs. Trả về true nếu đã xử lý (đã chặn paste mặc định).
   */
  async function handleImagePaste(event) {
    const dt = event.clipboardData;
    if (!dt) return false;

    const html = dt.getData('text/html') || '';
    const text = dt.getData('text/plain') || '';

    const fromClipboard = [];
    for (const item of Array.from(dt.items || [])) {
      if (item.kind === 'file' && /^image\//.test(item.type)) {
        const file = item.getAsFile();
        if (file && file.size > 0) fromClipboard.push(file);
      }
    }
    for (const file of Array.from(dt.files || [])) {
      if (/^image\//.test(file.type) && file.size > 0
        && !fromClipboard.some((known) => known.size === file.size && known.type === file.type)) {
        fromClipboard.push(file);
      }
    }

    const doc = html ? new DOMParser().parseFromString(html, 'text/html') : null;
    const dataImgs = [];
    const brokenImgs = [];
    if (doc) {
      for (const img of Array.from(doc.body.querySelectorAll('img'))) {
        const src = (img.getAttribute('src') || '').trim();
        if (/^data:image\//i.test(src)) dataImgs.push(img);
        else if (/^(file|blob):/i.test(src)) brokenImgs.push(img);
      }
    }

    const rtf = dt.getData('text/rtf') || '';
    const rtfFiles = rtf !== '' ? extractRtfImages(rtf) : [];

    if (fromClipboard.length === 0 && dataImgs.length === 0 && brokenImgs.length === 0 && rtfFiles.length === 0) {
      return false; // không có ảnh — để Quill xử lý paste bình thường
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const files = fromClipboard.slice();
    dataImgs.forEach((img, i) => {
      const file = dataUriToFile(img.getAttribute('src') || '', files.length + i);
      if (file) files.push(file);
    });
    // RTF là bản sao của ảnh đã có trong clipboard (Word đưa cả 2) -> chỉ dùng khi
    // không lấy được file/base64, tránh upload trùng 2 lần cùng một ảnh.
    if (files.length === 0) {
      rtfFiles.forEach((file) => files.push(file));
    }

    // Ảnh file:// không đọc được -> bỏ khỏi HTML để không thành <img> gãy.
    brokenImgs.forEach((img) => img.remove());
    dataImgs.forEach((img) => img.remove());

    const range = editor.getSelection(true) || { index: editor.getLength(), length: 0 };
    let insertAt = range.index;

    if (doc) {
      const cleaned = doc.body.innerHTML.trim();
      if (cleaned !== '') {
        const delta = editor.clipboard.convert({ html: cleaned, text });
        editor.updateContents(new Delta().retain(insertAt).delete(range.length).concat(delta), 'user');
        insertAt += delta.length();
      }
    } else if (text !== '') {
      editor.insertText(insertAt, text, 'user');
      insertAt += text.length;
    }

    if (files.length > 0) {
      await uploadImage({ index: insertAt, length: 0 }, files);
    }
    if (files.length === 0 && brokenImgs.length > 0) {
      announce('Không đọc được ảnh dán từ Word. Hãy lưu ảnh thành tệp rồi chèn bằng nút Ảnh.');
    }

    return true;
  }

  // capture = true: chạy trước listener paste của Quill (gắn trên editor.root).
  target.addEventListener('paste', (event) => {
    void handleImagePaste(event).catch(() => {
      announce('Không xử lý được ảnh dán vào. Hãy chèn ảnh bằng nút Ảnh.');
    });
  }, true);

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
