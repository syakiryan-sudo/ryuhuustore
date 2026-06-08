(function() {
  'use strict';

  // ========== DOM Elements ==========
  const countrySelect = document.getElementById('countryCode');
  const phoneInput = document.getElementById('phoneNumber');
  const messageInput = document.getElementById('messageText');
  const charCountSpan = document.getElementById('charCount');
  const openBtn = document.getElementById('openWhatsApp');
  const copyBtn = document.getElementById('copyLink');
  const resetBtn = document.getElementById('resetButton');
  const linkPreviewContainer = document.getElementById('linkPreviewContainer');
  const generatedLinkSpan = document.getElementById('generatedLink');
  const errorContainer = document.getElementById('errorMessage');
  const errorText = document.getElementById('errorText');
  const qrSection = document.getElementById('qrSection');
  const qrImage = document.getElementById('qrCodeImage');
  const historyList = document.getElementById('historyList');
  const historyEmpty = document.getElementById('historyEmpty');
  const clearHistoryBtn = document.getElementById('clearHistory');
  const toastContainer = document.getElementById('toastContainer');
  const darkModeToggle = document.getElementById('darkModeToggle');
  const body = document.body;

  // ========== Country Data ==========
  const countries = [
    { code: '62', name: 'Indonesia (+62)' },
    { code: '1', name: 'USA/Canada (+1)' },
    { code: '44', name: 'UK (+44)' },
    { code: '91', name: 'India (+91)' },
    { code: '81', name: 'Japan (+81)' },
    { code: '86', name: 'China (+86)' },
    { code: '61', name: 'Australia (+61)' },
    { code: '49', name: 'Germany (+49)' },
    { code: '33', name: 'France (+33)' },
    { code: '65', name: 'Singapore (+65)' },
    { code: '60', name: 'Malaysia (+60)' },
    { code: '966', name: 'Saudi Arabia (+966)' },
    { code: '971', name: 'UAE (+971)' },
    { code: '55', name: 'Brazil (+55)' },
    { code: '7', name: 'Russia (+7)' }
  ];

  // ========== State ==========
  let currentGeneratedNumber = '';
  let currentGeneratedMessage = '';

  // ========== Initialize Country Dropdown ==========
  function populateCountrySelect() {
    countrySelect.innerHTML = '';
    countries.forEach(country => {
      const option = document.createElement('option');
      option.value = country.code;
      option.textContent = country.name;
      if (country.code === '62') option.selected = true;
      countrySelect.appendChild(option);
    });
  }

  // ========== Helpers ==========
  function showError(message) {
    errorText.textContent = message;
    errorContainer.style.display = 'flex';
    linkPreviewContainer.style.display = 'none';
    qrSection.style.display = 'none';
  }

  function hideError() {
    errorContainer.style.display = 'none';
  }

  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.remove();
    }, 3000);
  }

  // Clean number: remove non-digits
  function cleanNumber(raw) {
    return raw.replace(/\D/g, '');
  }

  // Format number with country code
  function formatInternational(cleaned, countryCode) {
    // If starts with 0, replace leading 0 with country code
    if (cleaned.startsWith('0')) {
      return countryCode + cleaned.substring(1);
    }
    // If already has country code (starts with country code), keep as is
    if (cleaned.startsWith(countryCode)) {
      return cleaned;
    }
    // Otherwise, prepend country code
    return countryCode + cleaned;
  }

  // Validate that number contains enough digits (minimum 7 after country code)
  function isValidNumber(international) {
    // Remove any leading zeros after country code for checking length
    const digits = international.replace(/^0+/, ''); // shouldn't be needed
    return digits.length >= 8; // at least country code + 7 digits
  }

  // Generate WhatsApp URL
  function generateWaLink(number, message) {
    let base = `https://wa.me/${number}`;
    if (message && message.trim() !== '') {
      base += `?text=${encodeURIComponent(message.trim())}`;
    }
    return base;
  }

  // Update UI with generated link
  function updateUI(number, message) {
    hideError();
    const link = generateWaLink(number, message);
    generatedLinkSpan.textContent = link;
    linkPreviewContainer.style.display = 'block';
    // QR Code
    qrSection.style.display = 'block';
    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(link)}`;
    qrImage.src = qrApiUrl;
    // Store current
    currentGeneratedNumber = number;
    currentGeneratedMessage = message || '';
  }

  // Process input and generate
  function processAndGenerate() {
    const countryCode = countrySelect.value.trim();
    const rawPhone = phoneInput.value.trim();
    const message = messageInput.value.trim();

    if (!countryCode) {
      showError('Pilih kode negara terlebih dahulu.');
      return false;
    }
    if (!rawPhone) {
      showError('Nomor WhatsApp tidak boleh kosong.');
      return false;
    }

    const cleaned = cleanNumber(rawPhone);
    if (cleaned.length === 0) {
      showError('Nomor tidak valid. Masukkan setidaknya beberapa digit.');
      return false;
    }

    const international = formatInternational(cleaned, countryCode);
    if (!isValidNumber(international)) {
      showError('Nomor terlalu pendek. Pastikan nomor memiliki minimal 7 digit setelah kode negara.');
      return false;
    }

    updateUI(international, message);
    return true;
  }

  // Add to history (localStorage)
  function addToHistory(number, message, countryCode) {
    let history = JSON.parse(localStorage.getItem('wa_history') || '[]');
    const entry = {
      number: number,
      message: message || '',
      countryCode: countryCode,
      timestamp: new Date().toISOString()
    };
    // Remove duplicates (same number & message)
    history = history.filter(item => !(item.number === number && item.message === (message || '')));
    history.unshift(entry);
    // Keep only last 5
    if (history.length > 5) history = history.slice(0, 5);
    localStorage.setItem('wa_history', JSON.stringify(history));
    renderHistory();
  }

  // Render history from localStorage
  function renderHistory() {
    const history = JSON.parse(localStorage.getItem('wa_history') || '[]');
    historyList.innerHTML = '';
    if (history.length === 0) {
      historyEmpty.style.display = 'block';
      clearHistoryBtn.style.display = 'none';
    } else {
      historyEmpty.style.display = 'none';
      clearHistoryBtn.style.display = 'inline-flex';
      history.forEach((entry, index) => {
        const item = document.createElement('div');
        item.className = 'history-item';
        const date = new Date(entry.timestamp);
        const timeStr = date.toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        item.innerHTML = `
          <span class="history-phone" title="${entry.number}">${entry.countryCode ? '+' + entry.countryCode + ' ' : ''}${entry.number.slice(entry.countryCode.length)}</span>
          <span class="history-message">${entry.message ? entry.message.substring(0, 30) + (entry.message.length > 30 ? '...' : '') : 'Tanpa pesan'}</span>
          <span class="history-time">${timeStr}</span>
        `;
        item.addEventListener('click', () => {
          // Fill form with history data
          if (entry.countryCode) {
            countrySelect.value = entry.countryCode;
          }
          // To fill phone input, we need to show local number (minus country code if possible)
          let localNumber = entry.number;
          if (entry.countryCode && entry.number.startsWith(entry.countryCode)) {
            localNumber = entry.number.slice(entry.countryCode.length);
            // If original number had leading zero (Indonesian style), we don't know, but we can prepend 0 for Indonesia if desired? We'll just put as is.
          }
          phoneInput.value = localNumber;
          messageInput.value = entry.message || '';
          // Update char count
          updateCharCount();
          // Trigger process
          processAndGenerate();
        });
        historyList.appendChild(item);
      });
    }
  }

  function clearAllHistory() {
    localStorage.removeItem('wa_history');
    renderHistory();
    showToast('Riwayat berhasil dihapus.');
  }

  // Update character count
  function updateCharCount() {
    const len = messageInput.value.length;
    charCountSpan.textContent = `${len} / 500`;
  }

  // ========== Event Listeners ==========
  openBtn.addEventListener('click', () => {
    if (processAndGenerate()) {
      const link = generatedLinkSpan.textContent;
      window.open(link, '_blank', 'noopener,noreferrer');
      // Save to history
      addToHistory(currentGeneratedNumber, currentGeneratedMessage, countrySelect.value);
    }
  });

  copyBtn.addEventListener('click', async () => {
    if (processAndGenerate()) {
      const link = generatedLinkSpan.textContent;
      try {
        await navigator.clipboard.writeText(link);
        showToast('Link berhasil disalin!');
        addToHistory(currentGeneratedNumber, currentGeneratedMessage, countrySelect.value);
      } catch {
        showToast('Gagal menyalin link.', 'error');
      }
    }
  });

  resetBtn.addEventListener('click', () => {
    phoneInput.value = '';
    messageInput.value = '';
    countrySelect.value = '62';
    linkPreviewContainer.style.display = 'none';
    qrSection.style.display = 'none';
    hideError();
    updateCharCount();
    currentGeneratedNumber = '';
    currentGeneratedMessage = '';
  });

  // Live preview as user types (optional but good UX)
  phoneInput.addEventListener('input', () => {
    const raw = phoneInput.value.trim();
    if (raw.length > 2) {
      processAndGenerate();
    } else {
      linkPreviewContainer.style.display = 'none';
      qrSection.style.display = 'none';
      hideError();
    }
  });

  messageInput.addEventListener('input', () => {
    updateCharCount();
    // Re-generate if number already processed
    if (currentGeneratedNumber) {
      const msg = messageInput.value.trim();
      currentGeneratedMessage = msg;
      updateUI(currentGeneratedNumber, msg);
    }
  });

  countrySelect.addEventListener('change', () => {
    if (phoneInput.value.trim().length > 0) {
      processAndGenerate();
    }
  });

  // Dark Mode Toggle
  darkModeToggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    const isDark = body.classList.contains('dark');
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    // Update toggle icon
    const iconSpan = darkModeToggle.querySelector('.toggle-icon');
    iconSpan.textContent = isDark ? '☀️' : '🌙';
  });

  // Load dark mode preference
  function loadDarkMode() {
    const saved = localStorage.getItem('darkMode');
    if (saved === 'enabled' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      body.classList.add('dark');
      darkModeToggle.querySelector('.toggle-icon').textContent = '☀️';
    } else {
      body.classList.remove('dark');
      darkModeToggle.querySelector('.toggle-icon').textContent = '🌙';
    }
  }

  // Clear history button
  clearHistoryBtn.addEventListener('click', clearAllHistory);

  // ========== URL Parameter Support ==========
  function loadFromUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const phoneParam = params.get('phone');
    const textParam = params.get('text');
    if (phoneParam) {
      // Set phone number (assumes it's already international or could have country code)
      // We'll try to detect country code or default to 62
      let detectedCode = '62';
      let localNumber = phoneParam;
      // Simple detection: if starts with 62, etc.
      for (const country of countries) {
        if (phoneParam.startsWith(country.code) && country.code !== '62') {
          detectedCode = country.code;
          localNumber = phoneParam.slice(country.code.length);
          break;
        }
      }
      // For Indonesia, if starts with 62
      if (phoneParam.startsWith('62') && detectedCode === '62') {
        localNumber = phoneParam.slice(2);
      }
      countrySelect.value = detectedCode;
      phoneInput.value = localNumber;
      if (textParam) {
        messageInput.value = decodeURIComponent(textParam);
        updateCharCount();
      }
      // trigger generation
      processAndGenerate();
    }
  }

  // ========== Init ==========
  populateCountrySelect();
  loadDarkMode();
  renderHistory();
  updateCharCount();
  loadFromUrlParams();

  // Update message char count on input
  messageInput.addEventListener('input', updateCharCount);
})();
