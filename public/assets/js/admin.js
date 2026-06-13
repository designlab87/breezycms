(function () {
    'use strict';

    // ---------------------------------------------------------------------
    // Rich text editors (Quill)
    // ---------------------------------------------------------------------
    // Theme fonts available in the editor (keys map to ql-font-<key> classes,
    // styled in fonts.css/admin.css/site.css). "system" is Quill's default
    // (no class) so it is excluded from the whitelist.
    var FONT_WHITELIST = Object.keys(window.THEME_FONTS || {}).filter(function (k) {
        return k !== 'system';
    });

    if (typeof Quill !== 'undefined' && FONT_WHITELIST.length) {
        var Font = Quill.import('formats/font');
        Font.whitelist = FONT_WHITELIST;
        Quill.register(Font, true);
    }

    // Color palette for the text/background pickers: Quill's standard swatches,
    // the site's theme colors up front, and a trailing "custom" swatch that
    // opens a native color picker for any hex value. Sentinel handled below.
    var CUSTOM_COLOR = 'custom-color';
    var STANDARD_COLORS = [
        '#000000', '#444444', '#666666', '#999999', '#cccccc', '#ffffff',
        '#e60000', '#ff9900', '#ffff00', '#008a00', '#0066cc', '#9933ff',
        '#facccc', '#ffebcc', '#ffffcc', '#cce8cc', '#cce0f5', '#ebd6ff'
    ];

    function buildColorPalette() {
        var themeColors = (window.THEME_COLORS || []).filter(function (c) {
            return typeof c === 'string' && c !== '';
        });
        var seen = {};
        var palette = [];
        themeColors.concat(STANDARD_COLORS).forEach(function (c) {
            var key = c.toLowerCase();
            if (!seen[key]) { seen[key] = true; palette.push(c); }
        });
        palette.push(CUSTOM_COLOR);
        return palette;
    }

    var COLOR_PALETTE = buildColorPalette();

    var QUILL_TOOLBAR = [
        [{ header: [1, 2, 3, false] }, { font: FONT_WHITELIST.length ? [false].concat(FONT_WHITELIST) : [] }],
        ['bold', 'italic', 'underline'],
        [{ color: COLOR_PALETTE }, { background: COLOR_PALETTE }],
        [{ align: [] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'blockquote'],
        ['table'],
        ['clean']
    ];

    // Table actions exposed via the toolbar. Quill's built-in table module ships
    // the data model + key bindings but no UI, so we drive it from these buttons.
    // The "table" handler inserts a grid; the rest operate on the current cell.
    var TABLE_ROWS = 3;
    var TABLE_COLS = 3;

    // Normalize a typed hex value to #rgb/#rrggbb, or null if invalid/empty.
    function normalizeHex(value) {
        var v = (value || '').trim();
        if (v === '') { return null; }
        if (v.charAt(0) !== '#') { v = '#' + v; }
        return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v) ? v.toLowerCase() : null;
    }

    // Shared inline hex popover for the "custom" color swatch (replaces the
    // browser prompt). Created once and re-anchored to whichever button opened it.
    var hexPopover = null;
    var hexPopoverClose = null;

    function buildHexPopover() {
        var pop = document.createElement('div');
        pop.className = 'ql-hex-popover';
        pop.innerHTML =
            '<div class="ql-hex-popover__row">' +
                '<span class="ql-hex-popover__swatch"></span>' +
                '<input type="text" class="ql-hex-popover__input" placeholder="#1b5579" spellcheck="false" autocomplete="off" maxlength="7">' +
            '</div>' +
            '<div class="ql-hex-popover__actions">' +
                '<button type="button" class="btn btn--small ql-hex-popover__apply">Apply</button>' +
                '<button type="button" class="btn btn--small btn--ghost ql-hex-popover__cancel">Cancel</button>' +
            '</div>';
        document.body.appendChild(pop);
        return pop;
    }

    function openHexPopover(quill, format, anchorEl) {
        if (hexPopoverClose) { hexPopoverClose(); }
        if (!hexPopover) { hexPopover = buildHexPopover(); }

        var pop = hexPopover;
        var input = pop.querySelector('.ql-hex-popover__input');
        var swatch = pop.querySelector('.ql-hex-popover__swatch');
        var applyBtn = pop.querySelector('.ql-hex-popover__apply');
        var cancelBtn = pop.querySelector('.ql-hex-popover__cancel');

        // Remember the selection — focusing the input clears it.
        var range = quill.getSelection(true);

        input.value = '';
        input.classList.remove('is-invalid');
        swatch.style.background = '';

        var rect = anchorEl.getBoundingClientRect();
        pop.style.top = (rect.bottom + window.scrollY + 6) + 'px';
        pop.style.left = (rect.left + window.scrollX) + 'px';
        pop.classList.add('is-open');

        var onInput = function () {
            input.classList.remove('is-invalid');
            swatch.style.background = normalizeHex(input.value) || '';
        };
        var apply = function () {
            var hex = normalizeHex(input.value);
            if (!hex) { input.classList.add('is-invalid'); input.focus(); return; }
            if (range) { quill.setSelection(range.index, range.length); }
            quill.format(format, hex);
            close();
        };
        var onKey = function (e) {
            if (e.key === 'Enter') { e.preventDefault(); apply(); }
            else if (e.key === 'Escape') { e.preventDefault(); close(); }
        };
        var onOutside = function (e) {
            if (!pop.contains(e.target)) { close(); }
        };
        function close() {
            pop.classList.remove('is-open');
            input.removeEventListener('input', onInput);
            input.removeEventListener('keydown', onKey);
            applyBtn.removeEventListener('click', apply);
            cancelBtn.removeEventListener('click', close);
            document.removeEventListener('mousedown', onOutside, true);
            hexPopoverClose = null;
        }

        input.addEventListener('input', onInput);
        input.addEventListener('keydown', onKey);
        applyBtn.addEventListener('click', apply);
        cancelBtn.addEventListener('click', close);
        // Defer so the click that opened the popover doesn't immediately close it.
        setTimeout(function () { document.addEventListener('mousedown', onOutside, true); }, 0);
        setTimeout(function () { input.focus(); }, 20);

        hexPopoverClose = close;
    }

    // Replace the default color/background handlers so the custom swatch opens
    // the inline hex popover; all other swatches behave normally.
    function registerColorHandlers(quill) {
        var toolbar = quill.getModule('toolbar');
        ['color', 'background'].forEach(function (format) {
            toolbar.addHandler(format, function (value) {
                if (value === CUSTOM_COLOR) {
                    var anchor = toolbar.container.querySelector('.ql-' + format) || toolbar.container;
                    openHexPopover(quill, format, anchor);
                } else {
                    quill.format(format, value || false);
                }
            });
        });
    }

    // Initialize a single .quill-editor element backed by a hidden source field.
    function initEditor(el) {
        if (typeof Quill === 'undefined' || !el || el.dataset.quillReady) {
            return null;
        }
        var targetId = el.getAttribute('data-target');
        var source = document.getElementById(targetId);
        if (!source) {
            return null;
        }

        var quill = new Quill(el, { theme: 'snow', modules: { toolbar: QUILL_TOOLBAR } });
        quill.__source = source;
        registerColorHandlers(quill);
        el.dataset.quillReady = '1';

        if (source.value.trim() !== '') {
            quill.clipboard.dangerouslyPasteHTML(source.value);
        }

        var sync = function () {
            var html = quill.root.innerHTML;
            source.value = (html === '<p><br></p>') ? '' : html;
            // Quill changes (typing and toolbar formatting) don't reliably emit
            // native input events, so dispatch one from the source field. The
            // page builder listens on the form to flag unsaved changes.
            source.dispatchEvent(new Event('input', { bubbles: true }));
        };
        quill.on('text-change', sync);

        var form = el.closest('form');
        if (form) {
            form.addEventListener('submit', sync);
        }
        return quill;
    }

    function initEditors(root) {
        (root || document).querySelectorAll('.quill-editor').forEach(initEditor);
    }

    // ---------------------------------------------------------------------
    // Media picker modal (event-delegated so dynamically added fields work)
    // ---------------------------------------------------------------------
    var modal = document.getElementById('mediaModal');
    var activeField = null;

    function setPreview(field, item) {
        var preview = field.querySelector('.media-field__preview');
        var type = field.getAttribute('data-type');
        preview.innerHTML = '';
        if (type === 'image') {
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = '';
            preview.appendChild(img);
        } else if (type === 'video') {
            var v = document.createElement('video');
            v.src = item.url;
            v.controls = true;
            v.preload = 'metadata';
            preview.appendChild(v);
        } else {
            var chip = document.createElement('span');
            chip.className = 'file-chip';
            chip.textContent = '\uD83D\uDCCE ' + item.original_name;
            preview.appendChild(chip);
        }
    }

    function selectItem(item) {
        if (!activeField) {
            return;
        }
        var idInput = activeField.querySelector('.media-field__id');
        idInput.value = item.id;
        setPreview(activeField, item);
        idInput.dispatchEvent(new Event('change', { bubbles: true }));
        closeModal();
    }

    function renderGrid(items) {
        var grid = modal.querySelector('[data-grid]');
        grid.innerHTML = '';
        if (!items.length) {
            grid.innerHTML = '<p class="muted">Nothing here yet — upload above.</p>';
            return;
        }
        items.forEach(function (item) {
            var tile = document.createElement('button');
            tile.type = 'button';
            tile.className = 'picker-tile';
            if (item.type === 'image') {
                tile.innerHTML = '<img src="' + item.url + '" alt="">';
            } else if (item.type === 'video') {
                tile.innerHTML = '<video src="' + item.url + '" preload="metadata"></video>';
            } else {
                tile.innerHTML = '<span class="picker-tile__file">\uD83D\uDCCE</span>';
            }
            var name = document.createElement('span');
            name.className = 'picker-tile__name';
            name.textContent = item.original_name;
            tile.appendChild(name);
            tile.addEventListener('click', function () { selectItem(item); });
            grid.appendChild(tile);
        });
    }

    function loadList(type) {
        var url = modal.getAttribute('data-list-url') + '?type=' + encodeURIComponent(type);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderGrid(data.items || []); })
            .catch(function () { renderGrid([]); });
    }

    function openModal(field) {
        if (!modal) {
            return;
        }
        activeField = field;
        var type = field.getAttribute('data-media-type') || 'image';
        modal.querySelector('.media-modal__type').value = type;
        modal.querySelector('.media-modal__status').textContent = '';
        modal.querySelector('.media-modal__title').textContent = 'Media — ' + type;
        modal.hidden = false;
        loadList(type);
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
        activeField = null;
    }

    function initMediaPickers() {
        // Delegated handlers so they apply to widgets added after page load.
        document.addEventListener('click', function (e) {
            var pick = e.target.closest ? e.target.closest('.media-pick') : null;
            if (pick) {
                openModal(pick.closest('.media-field'));
                return;
            }
            var clear = e.target.closest ? e.target.closest('.media-clear') : null;
            if (clear) {
                var field = clear.closest('.media-field');
                var idInput = field.querySelector('.media-field__id');
                idInput.value = '';
                field.querySelector('.media-field__preview').innerHTML = '';
                idInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        if (!modal) {
            return;
        }

        modal.querySelectorAll('[data-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        var uploadForm = modal.querySelector('[data-upload]');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var status = modal.querySelector('.media-modal__status');
                status.textContent = 'Uploading…';
                var data = new FormData(uploadForm);
                data.set('type', modal.querySelector('.media-modal__type').value);
                fetch(modal.getAttribute('data-upload-url'), {
                    method: 'POST',
                    body: data,
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                    .then(function (res) {
                        if (!res.ok || res.body.error) {
                            status.textContent = res.body.error || 'Upload failed.';
                            return;
                        }
                        status.textContent = 'Uploaded.';
                        uploadForm.reset();
                        selectItem(res.body.item);
                    })
                    .catch(function () { status.textContent = 'Upload failed.'; });
            });
        }
    }

    // Expose helpers for the page builder (dynamic widgets).
    window.CMS = {
        initEditor: initEditor,
        initEditors: initEditors,
        quillToolbar: QUILL_TOOLBAR
    };

    function initPagePasswordToggle() {
        var cb = document.getElementById('isProtected');
        var field = document.getElementById('pagePasswordField');
        if (!cb || !field) { return; }
        cb.addEventListener('change', function () { field.hidden = !cb.checked; });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initEditors();
        initMediaPickers();
        initPagePasswordToggle();
    });
})();
