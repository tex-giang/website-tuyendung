/**
 * TEX-GIANG RECRUITMENT PORTAL - ONLINE APPLICATION JAVASCRIPT
 * Handles client-side form validation, CV drag & drop upload preview, progress simulation.
 */

document.addEventListener('DOMContentLoaded', function () {
  const applyForm = document.getElementById('online-apply-form');
  const dropZone = document.getElementById('cv-drop-zone');
  const fileInput = document.getElementById('cv-file-input');
  const filePreview = document.getElementById('file-preview-card');
  const fileNameDisplay = document.getElementById('file-name-display');
  const fileSizeDisplay = document.getElementById('file-size-display');
  const removeFileBtn = document.getElementById('remove-file-btn');
  const progressBar = document.getElementById('upload-progress-bar');
  const progressContainer = document.getElementById('upload-progress-container');

  // Pre-fill job title from URL parameter if passed (e.g. apply.html?job=Công%20Nhân%20May)
  const urlParams = new URLSearchParams(window.location.search);
  const prefilledJob = urlParams.get('job');
  const jobSelect = document.getElementById('apply-position');
  if (prefilledJob && jobSelect) {
    // If exact option exists, select it; otherwise add or set
    let found = false;
    for (let i = 0; i < jobSelect.options.length; i++) {
      if (jobSelect.options[i].text.toLowerCase().includes(prefilledJob.toLowerCase())) {
        jobSelect.selectedIndex = i;
        found = true;
        break;
      }
    }
  }

  // Setup Drag & Drop Zone
  if (dropZone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-active');
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-active');
      });
    });

    dropZone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length > 0) {
        handleFileSelection(files[0]);
      }
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files && fileInput.files.length > 0) {
        handleFileSelection(fileInput.files[0]);
      }
    });

    if (removeFileBtn) {
      removeFileBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fileInput.value = '';
        if (filePreview) filePreview.classList.add('hidden');
        if (dropZone) dropZone.classList.remove('hidden');
      });
    }
  }

  function handleFileSelection(file) {
    // Validate file extension
    const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExt)) {
      showToast('Định dạng tệp không được hỗ trợ. Vui lòng tải file PDF, DOC, DOCX hoặc ảnh JPG/PNG!', 'error');
      return;
    }

    // Validate size (max 10MB)
    const maxSizeMB = 10;
    if (file.size > maxSizeMB * 1024 * 1024) {
      showToast(`Dung lượng tệp vượt quá ${maxSizeMB}MB. Vui lòng chọn tệp nhỏ hơn!`, 'error');
      return;
    }

    // Show upload progress simulation
    if (progressContainer && progressBar) {
      progressContainer.classList.remove('hidden');
      progressBar.style.width = '0%';
      let progress = 0;
      const interval = setInterval(() => {
        progress += 25;
        progressBar.style.width = `${progress}%`;
        if (progress >= 100) {
          clearInterval(interval);
          setTimeout(() => {
            progressContainer.classList.add('hidden');
            if (dropZone) dropZone.classList.add('hidden');
            if (filePreview) {
              filePreview.classList.remove('hidden');
              if (fileNameDisplay) fileNameDisplay.textContent = file.name;
              if (fileSizeDisplay) fileSizeDisplay.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
            }
            showToast('Tải tệp CV thành công (Chế độ mô phỏng demo)!', 'success');
          }, 300);
        }
      }, 100);
    }
  }

  // Handle Form Submission
  if (applyForm) {
    applyForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const fullname = document.getElementById('apply-fullname')?.value.trim();
      const phone = document.getElementById('apply-phone')?.value.trim();
      const position = document.getElementById('apply-position')?.value;
      const termsAgreed = document.getElementById('apply-terms')?.checked;

      // Validation
      if (!fullname) {
        showToast('Vui lòng nhập họ và tên của bạn!', 'error');
        return;
      }

      const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
      if (!phone || !phoneRegex.test(phone.replace(/\s+/g, ''))) {
        showToast('Vui lòng nhập số điện thoại hợp lệ (10 chữ số)!', 'error');
        return;
      }

      if (!position) {
        showToast('Vui lòng chọn vị trí muốn ứng tuyển!', 'error');
        return;
      }

      if (!termsAgreed) {
        showToast('Vui lòng đồng ý với điều khoản xử lý dữ liệu cá nhân!', 'error');
        return;
      }

      // Submit feedback & redirect to success page
      const submitBtn = applyForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          Đang gửi hồ sơ...
        `;
      }

      setTimeout(() => {
        window.location.href = 'application-success.html';
      }, 1200);
    });
  }
});
