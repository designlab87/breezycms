(function () {
    'use strict';

    var canvas = document.getElementById('builderCanvas');
    var form = document.getElementById('builderForm');
    var hidden = document.getElementById('layoutJson');
    if (!canvas || !form || !hidden || typeof Sortable === 'undefined') {
        return;
    }

    var mediaPrefix = canvas.getAttribute('data-media-prefix') || '/media/';
    var uid = 0;
    function nextId() { return 'q' + (++uid) + '_' + Date.now(); }

    function el(html) {
        var t = document.createElement('template');
        t.innerHTML = html.trim();
        return t.content.firstElementChild;
    }

    // ---------------------------------------------------------------------
    // Widgets
    // ---------------------------------------------------------------------
    function buildWidget(type, value) {
        value = value || {};
        var widget = el(
            '<div class="builder-widget" data-widget-type="' + type + '">' +
                '<div class="builder-widget__head">' +
                    '<span class="builder-widget__handle" title="Drag">⠿</span>' +
                    '<span class="builder-widget__label"></span>' +
                    '<button type="button" class="builder-x" title="Remove widget">×</button>' +
                '</div>' +
                '<div class="builder-widget__body"></div>' +
            '</div>'
        );
        var labels = { richtext: 'Rich text', image: 'Image', video: 'Video', file: 'File download', button: 'Button' };
        widget.querySelector('.builder-widget__label').textContent = labels[type] || type;
        var body = widget.querySelector('.builder-widget__body');

        if (type === 'richtext') {
            var id = nextId();
            var wrap = el(
                '<div class="quill-wrap">' +
                    '<div class="quill-editor" data-target="' + id + '"></div>' +
                    '<textarea class="quill-source" id="' + id + '" hidden></textarea>' +
                '</div>'
            );
            wrap.querySelector('.quill-source').value = typeof value === 'string' ? value : (value.value || '');
            body.appendChild(wrap);
            setTimeout(function () {
                if (window.CMS && window.CMS.initEditor) {
                    window.CMS.initEditor(wrap.querySelector('.quill-editor'));
                }
            }, 0);
        } else if (type === 'button') {
            body.appendChild(buildButtonField(value));
        } else {
            body.appendChild(buildMediaField(type, value));
        }

        widget.querySelector('.builder-x').addEventListener('click', function () {
            widget.remove();
            updateEmptyState();
            markDirty();
        });
        return widget;
    }

    function buildMediaField(type, value) {
        var field = el(
            '<div class="field media-field" data-type="' + type + '" data-media-type="' + type + '">' +
                '<div class="media-field__preview"></div>' +
                '<input type="hidden" class="media-field__id">' +
                '<div class="media-field__actions">' +
                    '<button type="button" class="btn btn--small media-pick">Choose / upload ' + type + '</button>' +
                    '<button type="button" class="btn btn--small btn--ghost media-clear">Remove</button>' +
                '</div>' +
            '</div>'
        );
        field.querySelector('.media-field__id').value = value.media_id || '';

        var actions = field.querySelector('.media-field__actions');
        if (type === 'image') {
            // Group the editable settings (alt, width, align) below the preview
            // and the choose/remove actions so the widget reads top-to-bottom.
            var meta = el(
                '<div class="image-field__meta">' +
                    '<label class="image-field__ctl image-field__ctl--full"><span>Alt text</span>' +
                        '<input type="text" class="media-field__alt" placeholder="Describe the image (for accessibility)">' +
                    '</label>' +
                    '<div class="image-field__opts">' +
                        '<label class="image-field__ctl"><span>Width</span>' +
                            '<span class="image-field__width">' +
                                '<input type="number" class="media-field__width" min="1" max="4000" placeholder="Native">' +
                                '<span class="image-field__unit">px</span>' +
                            '</span>' +
                        '</label>' +
                        '<label class="image-field__ctl"><span>Alignment</span>' +
                            '<select class="media-field__align">' +
                                '<option value="left">Left</option>' +
                                '<option value="center">Center</option>' +
                                '<option value="right">Right</option>' +
                            '</select>' +
                        '</label>' +
                    '</div>' +
                '</div>'
            );
            meta.querySelector('.media-field__alt').value = value.alt || '';
            meta.querySelector('.media-field__width').value = value.width ? value.width : '';
            meta.querySelector('.media-field__align').value =
                (value.align === 'center' || value.align === 'right') ? value.align : 'left';
            field.appendChild(meta);
        } else if (type === 'file') {
            var label = el('<input type="text" class="media-field__label" placeholder="Link label (e.g. Download flyer)">');
            label.value = value.label || '';
            field.insertBefore(label, actions);
        } else if (type === 'video') {
            var url = el('<input type="url" class="media-field__url" placeholder="…or paste an embed URL">');
            url.value = value.url || '';
            field.insertBefore(url, actions);
        }

        renderMediaPreview(field, type, value);
        return field;
    }

    function renderMediaPreview(field, type, value) {
        var preview = field.querySelector('.media-field__preview');
        preview.innerHTML = '';
        var mid = value.media_id || '';
        if ((type === 'image') && mid) {
            var img = document.createElement('img');
            img.src = mediaPrefix + mid;
            preview.appendChild(img);
        } else if (type === 'video' && mid) {
            var v = document.createElement('video');
            v.src = mediaPrefix + mid;
            v.controls = true;
            v.preload = 'metadata';
            preview.appendChild(v);
        } else if (type === 'file' && mid) {
            var chip = document.createElement('span');
            chip.className = 'file-chip';
            chip.textContent = '\uD83D\uDCCE ' + (value.label || 'Selected file');
            preview.appendChild(chip);
        }
    }

    function buildButtonField(value) {
        value = value || {};
        var wrap = el(
            '<div class="button-field">' +
                '<input type="text" class="button-field__text" placeholder="Button text">' +
                '<input type="text" class="button-field__url" placeholder="Link URL (https://… or /page/slug)">' +
                '<div class="button-field__row">' +
                    '<label class="button-field__ctl"><span>Style</span>' +
                        '<select class="button-field__variant">' +
                            '<option value="primary">Primary</option>' +
                            '<option value="accent">Accent</option>' +
                        '</select>' +
                    '</label>' +
                    '<label class="button-field__ctl"><span>Size</span>' +
                        '<select class="button-field__size">' +
                            '<option value="normal">Normal</option>' +
                            '<option value="large">Large</option>' +
                        '</select>' +
                    '</label>' +
                    '<label class="button-field__ctl"><span>Alignment</span>' +
                        '<select class="button-field__align">' +
                            '<option value="left">Left</option>' +
                            '<option value="center">Center</option>' +
                            '<option value="right">Right</option>' +
                        '</select>' +
                    '</label>' +
                    '<label class="button-field__check"><input type="checkbox" class="button-field__full"> Full width</label>' +
                '</div>' +
            '</div>'
        );
        wrap.querySelector('.button-field__text').value = value.text || '';
        wrap.querySelector('.button-field__url').value = value.url || '';
        wrap.querySelector('.button-field__variant').value = value.variant === 'accent' ? 'accent' : 'primary';
        wrap.querySelector('.button-field__size').value = value.size === 'large' ? 'large' : 'normal';
        wrap.querySelector('.button-field__align').value =
            (value.align === 'center' || value.align === 'right') ? value.align : 'left';
        wrap.querySelector('.button-field__full').checked = !!value.full_width;
        return wrap;
    }

    // ---------------------------------------------------------------------
    // Containers
    // ---------------------------------------------------------------------
    var RATIO_LAYOUTS = ['1-2', '1-3', '2-1', '3-1'];

    function normalizeLayout(layout) {
        return RATIO_LAYOUTS.indexOf(layout) >= 0 ? layout : 'equal';
    }

    function containerLabel(columns, layout) {
        return layout === 'equal'
            ? columns + '-column row'
            : 'Split row (' + layout.replace('-', ':') + ')';
    }

    function buildContainer(columns, colsData, bgColor, bgImage, bgFit, layout) {
        columns = Math.max(1, Math.min(4, parseInt(columns, 10) || 1));
        colsData = colsData || [];
        layout = normalizeLayout(layout);
        // Ratio layouts are always two columns.
        if (layout !== 'equal') { columns = 2; }
        var colsClass = layout === 'equal' ? 'row--cols-' + columns : 'row--ratio-' + layout;
        var container = el(
            '<div class="builder-container" data-columns="' + columns + '" data-layout="' + layout + '">' +
                '<div class="builder-container__head">' +
                    '<span class="builder-container__handle" title="Drag row">⠿</span>' +
                    '<span class="builder-container__label">' + containerLabel(columns, layout) + '</span>' +
                    '<span class="builder-container__bg">' +
                        '<span class="builder-container__bg-label">Background</span>' +
                        '<span class="container-color hex-field">' +
                            '<span class="hex-field__swatch"></span>' +
                            '<input type="text" class="container-color-input hex-field__input" placeholder="#hex" spellcheck="false" autocomplete="off" maxlength="7">' +
                        '</span>' +
                        '<span class="container-bg-image media-field" data-type="image" data-media-type="image">' +
                            '<input type="hidden" class="media-field__id">' +
                            '<span class="media-field__preview"></span>' +
                            '<button type="button" class="btn btn--small media-pick" title="Background image">Image</button>' +
                            '<button type="button" class="btn btn--small btn--ghost media-clear" title="Remove image" hidden>✕</button>' +
                        '</span>' +
                        '<select class="container-bg-fit" title="Background image fit" hidden>' +
                            '<option value="cover">Cover</option>' +
                            '<option value="contain">Contain</option>' +
                            '<option value="100%">100%</option>' +
                        '</select>' +
                    '</span>' +
                    '<button type="button" class="builder-x" title="Remove row">×</button>' +
                '</div>' +
                '<div class="builder-container__cols ' + colsClass + '"></div>' +
            '</div>'
        );
        if (bgColor) { container.dataset.bgColor = bgColor; }

        var colsWrap = container.querySelector('.builder-container__cols');
        for (var i = 0; i < columns; i++) {
            var col = el('<div class="builder-column"><div class="builder-column__hint">Drop widgets here</div><div class="builder-column__list"></div></div>');
            var list = col.querySelector('.builder-column__list');
            (colsData[i] || []).forEach(function (w) { list.appendChild(buildWidget(w.type, w.value)); });
            colsWrap.appendChild(col);
            makeColumnSortable(list);
        }

        initContainerColor(container, bgColor);

        // Background image + fit.
        var bgIdInput = container.querySelector('.container-bg-image .media-field__id');
        var bgFitSel = container.querySelector('.container-bg-fit');
        bgFitSel.value = ['cover', 'contain', '100%'].indexOf(bgFit) >= 0 ? bgFit : 'cover';
        if (bgImage) {
            bgIdInput.value = bgImage;
            var pv = container.querySelector('.container-bg-image .media-field__preview');
            pv.innerHTML = '<img src="' + mediaPrefix + bgImage + '" alt="">';
        }
        applyContainerBgImage(container);
        // The media modal dispatches a change on the id input after pick/clear.
        bgIdInput.addEventListener('change', function () { applyContainerBgImage(container); markDirty(); });
        bgFitSel.addEventListener('change', function () { applyContainerBgImage(container); markDirty(); });

        container.querySelector('.builder-x').addEventListener('click', function () {
            container.remove();
            updateEmptyState();
            markDirty();
        });
        return container;
    }

    /** Reflect the container's background image + fit in the builder (WYSIWYG). */
    function applyContainerBgImage(container) {
        var id = container.querySelector('.container-bg-image .media-field__id').value.trim();
        var fit = container.querySelector('.container-bg-fit').value;
        var cols = container.querySelector('.builder-container__cols');
        var fitSel = container.querySelector('.container-bg-fit');
        var clearBtn = container.querySelector('.container-bg-image .media-clear');
        if (id) {
            cols.style.backgroundImage = 'url("' + mediaPrefix + id + '")';
            cols.style.backgroundSize = (fit === '100%') ? '100%' : fit;
            cols.style.backgroundPosition = 'center';
            cols.style.backgroundRepeat = 'no-repeat';
            container.classList.add('has-bg-image');
            fitSel.hidden = false;
            clearBtn.hidden = false;
        } else {
            cols.style.backgroundImage = '';
            container.classList.remove('has-bg-image');
            fitSel.hidden = true;
            clearBtn.hidden = true;
        }
    }

    /** Normalize a typed hex value to #rgb/#rrggbb, or null if not valid/empty. */
    function normalizeHex(value) {
        var v = (value || '').trim();
        if (v === '') { return null; }
        if (v.charAt(0) !== '#') { v = '#' + v; }
        return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v) ? v.toLowerCase() : null;
    }

    /** Container background color via a plain hex input (no picker). */
    function initContainerColor(container, initial) {
        var input = container.querySelector('.container-color-input');
        var swatch = container.querySelector('.container-color .hex-field__swatch');

        var applyState = function (color) {
            if (color) {
                container.dataset.bgColor = color;
                container.style.setProperty('--container-bg', color);
                container.classList.add('has-bg');
                swatch.style.background = color;
            } else {
                delete container.dataset.bgColor;
                container.style.removeProperty('--container-bg');
                container.classList.remove('has-bg');
                swatch.style.background = '';
            }
        };

        var seed = normalizeHex(initial);
        input.value = seed || '';
        applyState(seed);

        input.addEventListener('input', function () {
            applyState(normalizeHex(input.value));
            markDirty();
        });
        input.addEventListener('blur', function () {
            var color = normalizeHex(input.value);
            input.value = color || '';
            applyState(color);
        });
    }

    // ---------------------------------------------------------------------
    // Hero
    // ---------------------------------------------------------------------
    function buildHero(data) {
        data = data || {};
        var bg = data.background || {};
        var bgKind = bg.kind === 'video' ? 'video' : 'image';
        var height = ['small', 'medium', 'large', 'full'].indexOf(data.height) >= 0 ? data.height : 'medium';
        var align = ['left', 'center', 'right'].indexOf(data.align) >= 0 ? data.align : 'center';

        var hero = el(
            '<div class="builder-hero">' +
                '<div class="builder-hero__head">' +
                    '<span class="builder-hero__handle" title="Drag hero">⠿</span>' +
                    '<span class="builder-hero__label">★ Hero</span>' +
                    '<button type="button" class="builder-x" title="Remove hero">×</button>' +
                '</div>' +
                '<div class="builder-hero__settings">' +
                    '<div class="hero-settings__row hero-settings__row--bg">' +
                        '<label class="hero-ctl"><span>Background</span>' +
                            '<select class="hero-bg-kind">' +
                                '<option value="image">Image</option>' +
                                '<option value="video">Video</option>' +
                            '</select>' +
                        '</label>' +
                        '<div class="hero-bg-holder"></div>' +
                    '</div>' +
                    '<div class="hero-settings__row hero-settings__row--opts">' +
                        '<label class="hero-ctl"><span>Height</span>' +
                            '<select class="hero-height">' +
                                '<option value="small">Small</option><option value="medium">Medium</option>' +
                                '<option value="large">Large</option><option value="full">Full screen</option>' +
                            '</select>' +
                        '</label>' +
                        '<label class="hero-ctl"><span>Content align</span>' +
                            '<select class="hero-align">' +
                                '<option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>' +
                            '</select>' +
                        '</label>' +
                        '<label class="hero-ctl"><span>Overlay</span>' +
                            '<select class="hero-overlay">' +
                                '<option value="none">None</option>' +
                                '<option value="dark">Dark</option>' +
                                '<option value="light">Light</option>' +
                            '</select>' +
                        '</label>' +
                    '</div>' +
                '</div>' +
                '<div class="builder-hero__content">' +
                    '<p class="builder-hero__content-label">Hero content</p>' +
                    '<div class="builder-column"><div class="builder-column__hint">Drop widgets here</div><div class="builder-column__list"></div></div>' +
                '</div>' +
            '</div>'
        );

        // Background media field (kind-aware).
        var holder = hero.querySelector('.hero-bg-holder');
        holder.appendChild(buildHeroBgField(bgKind, bg));

        var kindSel = hero.querySelector('.hero-bg-kind');
        kindSel.value = bgKind;
        kindSel.addEventListener('change', function () {
            holder.innerHTML = '';
            holder.appendChild(buildHeroBgField(kindSel.value === 'video' ? 'video' : 'image', {}));
            markDirty();
        });

        // Normalize overlay: legacy boolean true -> 'dark', false/absent -> 'none'.
        var overlay = data.overlay === true ? 'dark' : (data.overlay || 'none');
        if (['none', 'dark', 'light'].indexOf(overlay) < 0) { overlay = 'none'; }
        hero.querySelector('.hero-overlay').value = overlay;
        hero.querySelector('.hero-height').value = height;
        hero.querySelector('.hero-align').value = align;

        // Content widgets.
        var list = hero.querySelector('.builder-column__list');
        ((data.cols && data.cols[0]) || []).forEach(function (w) { list.appendChild(buildWidget(w.type, w.value)); });
        makeColumnSortable(list);

        hero.querySelector('.builder-x').addEventListener('click', function () {
            hero.remove();
            updateEmptyState();
            markDirty();
        });
        return hero;
    }

    function buildHeroBgField(kind, value) {
        value = value || {};
        var field = el(
            '<div class="field media-field hero-bg-field" data-type="' + kind + '" data-media-type="' + kind + '">' +
                '<div class="media-field__preview"></div>' +
                '<input type="hidden" class="media-field__id">' +
                '<div class="media-field__actions">' +
                    '<button type="button" class="btn btn--small media-pick">Choose / upload ' + kind + '</button>' +
                    '<button type="button" class="btn btn--small btn--ghost media-clear">Remove</button>' +
                '</div>' +
            '</div>'
        );
        field.querySelector('.media-field__id').value = value.media_id || '';
        renderMediaPreview(field, kind, value);
        return field;
    }

    // ---------------------------------------------------------------------
    // Sortable wiring
    // ---------------------------------------------------------------------
    function isWidgetEl(dragEl) {
        return dragEl.classList.contains('builder-widget') ||
            (dragEl.dataset && dragEl.dataset.kind === 'widget');
    }
    function isTopLevelEl(dragEl) {
        return dragEl.classList.contains('builder-container') ||
            dragEl.classList.contains('builder-hero') ||
            (dragEl.dataset && (dragEl.dataset.kind === 'container' || dragEl.dataset.kind === 'hero'));
    }

    function makeColumnSortable(list) {
        Sortable.create(list, {
            group: { name: 'widgets', put: function (to, from, dragEl) { return isWidgetEl(dragEl); } },
            draggable: '.builder-widget',
            handle: '.builder-widget__handle',
            animation: 150,
            ghostClass: 'builder-ghost',
            onAdd: function (evt) {
                var item = evt.item;
                if (item.dataset && item.dataset.kind === 'widget') {
                    item.replaceWith(buildWidget(item.dataset.widgetType, {}));
                }
                updateEmptyState();
                markDirty();
            },
            onSort: markDirty,
            onRemove: markDirty
        });
    }

    Sortable.create(canvas, {
        group: { name: 'containers', put: function (to, from, dragEl) { return isTopLevelEl(dragEl); } },
        draggable: '.builder-container, .builder-hero',
        handle: '.builder-container__handle, .builder-hero__handle',
        animation: 150,
        ghostClass: 'builder-ghost',
        onAdd: function (evt) {
            var item = evt.item;
            if (item.dataset && item.dataset.kind === 'hero') {
                item.replaceWith(buildHero({}));
            } else if (item.dataset && item.dataset.kind === 'container') {
                item.replaceWith(buildContainer(item.dataset.columns, [], null, null, null, item.dataset.layout));
            }
            updateEmptyState();
            markDirty();
        },
        onSort: function () { updateEmptyState(); markDirty(); }
    });

    Sortable.create(document.getElementById('paletteLayout'), {
        group: { name: 'containers', pull: 'clone', put: false }, sort: false
    });
    Sortable.create(document.getElementById('paletteWidgets'), {
        group: { name: 'widgets', pull: 'clone', put: false }, sort: false
    });

    // Palette tabs: show one group at a time to keep the palette compact.
    (function initPaletteTabs() {
        var tabs = document.querySelectorAll('.builder-palette__tab');
        var panels = document.querySelectorAll('.builder-palette__panel');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var name = tab.getAttribute('data-palette-tab');
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                panels.forEach(function (panel) {
                    panel.hidden = panel.getAttribute('data-palette-panel') !== name;
                });
            });
        });
    })();

    function updateEmptyState() {
        var empty = canvas.querySelector('.builder-canvas__empty');
        var hasItem = canvas.querySelector('.builder-container, .builder-hero');
        if (empty) { empty.style.display = hasItem ? 'none' : ''; }
    }

    // ---------------------------------------------------------------------
    // Serialize
    // ---------------------------------------------------------------------
    function readWidget(wEl) {
        var type = wEl.getAttribute('data-widget-type');
        var value = null;
        if (type === 'richtext') {
            var src = wEl.querySelector('.quill-source');
            value = src ? src.value : '';
        } else if (type === 'button') {
            value = {
                text: wEl.querySelector('.button-field__text').value,
                url: wEl.querySelector('.button-field__url').value,
                variant: wEl.querySelector('.button-field__variant').value,
                size: wEl.querySelector('.button-field__size').value,
                align: wEl.querySelector('.button-field__align').value,
                full_width: wEl.querySelector('.button-field__full').checked
            };
        } else {
            var field = wEl.querySelector('.media-field');
            var mid = field.querySelector('.media-field__id').value.trim();
            if (type === 'image') {
                if (mid) {
                    var altEl = field.querySelector('.media-field__alt');
                    var widthEl = field.querySelector('.media-field__width');
                    var alignEl = field.querySelector('.media-field__align');
                    value = { media_id: mid, alt: altEl ? altEl.value : '' };
                    var w = widthEl ? parseInt(widthEl.value, 10) : 0;
                    if (w > 0) { value.width = Math.min(w, 4000); }
                    var a = alignEl ? alignEl.value : 'left';
                    if (a === 'center' || a === 'right') { value.align = a; }
                } else {
                    value = null;
                }
            } else if (type === 'file') {
                var labelEl = field.querySelector('.media-field__label');
                value = mid ? { media_id: mid, label: labelEl ? labelEl.value : '' } : null;
            } else if (type === 'video') {
                var urlEl = field.querySelector('.media-field__url');
                var url = urlEl ? urlEl.value.trim() : '';
                if (mid) { value = { media_id: mid }; }
                else if (url) { value = { url: url }; }
            }
        }
        return { type: type, value: value };
    }

    function readColumnList(listEl) {
        var widgets = [];
        listEl.querySelectorAll(':scope > .builder-widget').forEach(function (wEl) {
            widgets.push(readWidget(wEl));
        });
        return widgets;
    }

    function readContainer(cEl) {
        var columns = parseInt(cEl.getAttribute('data-columns'), 10) || 1;
        var cols = [];
        cEl.querySelectorAll('.builder-column__list').forEach(function (listEl) {
            cols.push(readColumnList(listEl));
        });
        return {
            type: 'container',
            columns: columns,
            layout: normalizeLayout(cEl.dataset.layout),
            background_color: cEl.dataset.bgColor || null,
            background_image: cEl.querySelector('.container-bg-image .media-field__id').value.trim() || null,
            background_fit: cEl.querySelector('.container-bg-fit').value,
            cols: cols
        };
    }

    function readHero(hEl) {
        var bgField = hEl.querySelector('.hero-bg-field');
        var kind = hEl.querySelector('.hero-bg-kind').value === 'video' ? 'video' : 'image';
        var mid = bgField.querySelector('.media-field__id').value.trim();
        var list = hEl.querySelector('.builder-column__list');
        return {
            type: 'hero',
            background: mid ? { kind: kind, media_id: mid } : null,
            overlay: hEl.querySelector('.hero-overlay').value,
            height: hEl.querySelector('.hero-height').value,
            align: hEl.querySelector('.hero-align').value,
            cols: [readColumnList(list)]
        };
    }

    function serialize() {
        var layout = [];
        canvas.querySelectorAll(':scope > .builder-container, :scope > .builder-hero').forEach(function (node) {
            layout.push(node.classList.contains('builder-hero') ? readHero(node) : readContainer(node));
        });
        hidden.value = JSON.stringify(layout);
    }

    // ---------------------------------------------------------------------
    // Unsaved-changes tracking
    // ---------------------------------------------------------------------
    var statusEl = document.getElementById('builderStatus');
    var dirty = false;
    var started = false;
    var saving = false;

    function updateStatus() {
        if (!statusEl) { return; }
        statusEl.textContent = dirty ? 'Unsaved changes' : 'All changes saved';
        statusEl.classList.toggle('is-dirty', dirty);
        statusEl.classList.toggle('is-saved', !dirty);
    }

    function markDirty() {
        if (!started || dirty) { return; }
        dirty = true;
        updateStatus();
    }

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    window.addEventListener('beforeunload', function (e) {
        if (dirty && !saving) { e.preventDefault(); e.returnValue = ''; return ''; }
    });

    form.addEventListener('submit', function () {
        serialize();
        saving = true;
        dirty = false;
        updateStatus();
    });

    // ---------------------------------------------------------------------
    // Hydrate
    // ---------------------------------------------------------------------
    function hydrate() {
        var raw = canvas.getAttribute('data-initial');
        var layout = [];
        try { layout = JSON.parse(raw) || []; } catch (e) { layout = []; }
        layout.forEach(function (item) {
            canvas.appendChild(item.type === 'hero'
                ? buildHero(item)
                : buildContainer(item.columns, item.cols || [], item.background_color, item.background_image, item.background_fit, item.layout));
        });
        updateEmptyState();
    }

    hydrate();
    updateStatus();
    setTimeout(function () { started = true; }, 60);
})();
