/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

((tinyMCE, Joomla, window, document) => {
  'use strict';

  /**
   * Hide or Show editor toolbar on toogle button
   * @param Element toxToolbar  Tox Toolbar group element
   * @param int n               Number of element need to hide from behind
   * @param boolean toggle      Hide|Show Toollbar
   */
  const toggleTinymceToolbars = (toxToolbar, n = 10, toggle = true) => {
    let toolbars = toxToolbar.querySelectorAll('.tox-toolbar__group');
    if (toolbars.length > 0) {
      toolbars = [].slice.call(toolbars, 0).reverse();
      for (let i = 0; i < n; i += 1) {
        if (toggle) {
          toolbars[i].classList.add('d-none');
        } else {
          toolbars[i].classList.remove('d-none');
        }
      }
    }
  };

  Joomla.JoomlaTinyMCE = {
    /**
     * Find all TinyMCE elements and initialize TinyMCE instance for each
     *
     * @param {HTMLElement}  target  Target Element where to search for the editor element
     *
     * @since 3.7.0
     */
    setupEditors: (target) => {
      const container = target || document;
      const pluginOptions = Joomla.getOptions ? Joomla.getOptions('plg_editor_tinymce', {})
        : (Joomla.optionsStorage.plg_editor_tinymce || {});
      const editors = [].slice.call(container.querySelectorAll('.js-editor-tinymce'));

      editors.forEach((editor) => {
        const currentEditor = editor.querySelector('textarea');
        Joomla.JoomlaTinyMCE.setupEditor(currentEditor, pluginOptions);
      });
    },

    /**
     * Initialize TinyMCE editor instance
     *
     * @param {HTMLElement}  element
     * @param {Object}       pluginOptions
     *
     * @since 3.7.0
     */
    setupEditor: (element, pluginOptions) => {
      // Check whether the editor already has ben set
      if (Joomla.editors.instances[element.id]) {
        return;
      }

      const name = element ? element.getAttribute('name').replace(/\[\]|\]/g, '').split('[').pop() : 'default'; // Get Editor name
      const tinyMCEOptions = pluginOptions ? pluginOptions.tinyMCE || {} : {};
      const defaultOptions = tinyMCEOptions.default || {};
      // Check specific options by the name
      let options = tinyMCEOptions[name] ? tinyMCEOptions[name] : defaultOptions;

      // Avoid an unexpected changes, and copy the options object
      if (options.joomlaMergeDefaults) {
        options = Joomla.extend(Joomla.extend({}, defaultOptions), options);
      } else {
        options = Joomla.extend({}, options);
      }

      if (element) {
        // We already have the Target, so reset the selector and assign given element as target
        options.selector = null;
        options.target = element;
      }

      const buttonValues = [];
      const arr = Object.keys(options.joomlaExtButtons.names)
        .map(key => options.joomlaExtButtons.names[key]);

      const icons = {
        joomla: '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M8.313 8.646c1.026-1.026 2.688-1.026 3.713-0.001l0.245 0.246 3.159-3.161-0.246-0.246c-1.801-1.803-4.329-2.434-6.638-1.891-0.331-2.037-2.096-3.591-4.224-3.592-2.364 0-4.28 1.92-4.28 4.286 0 2.042 1.425 3.75 3.333 4.182-0.723 2.42-0.133 5.151 1.776 7.062l7.12 7.122 3.156-3.163-7.119-7.121c-1.021-1.023-1.023-2.691 0.006-3.722zM31.96 4.286c0-2.368-1.916-4.286-4.281-4.286-2.164 0-3.952 1.608-4.24 3.695-2.409-0.708-5.118-0.109-7.020 1.794l-7.12 7.122 3.159 3.162 7.118-7.12c1.029-1.030 2.687-1.028 3.709-0.006 1.025 1.026 1.025 2.691-0.001 3.717l-0.244 0.245 3.157 3.164 0.246-0.248c1.889-1.893 2.49-4.586 1.8-6.989 2.098-0.276 3.717-2.074 3.717-4.25zM28.321 23.471c0.566-2.327-0.062-4.885-1.878-6.703l-7.109-7.125-3.159 3.16 7.11 7.125c1.029 1.031 1.027 2.691 0.006 3.714-1.025 1.025-2.688 1.025-3.714-0.001l-0.243-0.243-3.156 3.164 0.242 0.241c1.922 1.925 4.676 2.514 7.105 1.765 0.395 1.959 2.123 3.431 4.196 3.431 2.363 0 4.28-1.917 4.28-4.285 0-2.163-1.599-3.952-3.679-4.244zM19.136 16.521l-7.111 7.125c-1.022 1.024-2.689 1.026-3.717-0.004-1.026-1.028-1.026-2.691-0.001-3.718l0.244-0.243-3.159-3.16-0.242 0.241c-1.836 1.838-2.455 4.432-1.858 6.781-1.887 0.446-3.292 2.145-3.292 4.172-0.001 2.367 1.917 4.285 4.281 4.285 2.034-0.001 3.737-1.419 4.173-3.324 2.334 0.58 4.906-0.041 6.729-1.867l7.109-7.124-3.157-3.163z"></path></svg>',
        toogle_icon: '<svg class="toggle-elipsis" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M328 256c0 39.8-32.2 72-72 72s-72-32.2-72-72 32.2-72 72-72 72 32.2 72 72zm104-72c-39.8 0-72 32.2-72 72s32.2 72 72 72 72-32.2 72-72-32.2-72-72-72zm-352 0c-39.8 0-72 32.2-72 72s32.2 72 72 72 72-32.2 72-72-32.2-72-72-72z"/></svg>',
      };

      arr.forEach((xtdButton) => {
        const tmp = {};
        tmp.text = xtdButton.name;
        tmp.icon = xtdButton.icon;
        tmp.type = 'menuitem';

        if (xtdButton.iconSVG) {
          icons[tmp.icon] = xtdButton.iconSVG;
        }

        if (xtdButton.href) {
          tmp.onAction = () => {
            document.getElementById(`${xtdButton.id}Modal`).open();
          };
        } else {
          tmp.onAction = () => {
            // eslint-disable-next-line no-new-func
            new Function(xtdButton.click)();
          };
        }

        buttonValues.push(tmp);
      });

      // Ensure tinymce is initialised in readonly mode if the textarea has readonly applied
      let readOnlyMode = false;

      if (element) {
        readOnlyMode = element.readOnly;
      }
      /**
       * Toggle menubar and some tools
       * @param {Object} editor
       *
       * @since 4.0.0
       */
      const toggleButtonValues = (editor) => {
        const { parentNode } = editor.editorContainer;
        const toxToolbar = editor.editorContainer.querySelector('.tox-toolbar');
        if (parentNode !== null) {
          if (parentNode.classList.contains('joomla-tinymce-hide-menu')) {
            parentNode.classList.remove('joomla-tinymce-hide-menu');
            toggleTinymceToolbars(toxToolbar, 12, false);
          } else {
            toggleTinymceToolbars(toxToolbar, 12);
            parentNode.classList.add('joomla-tinymce-hide-menu');
          }
        }
      };

      if (buttonValues.length) {
        options.setup = (editor) => {
          editor.settings.readonly = readOnlyMode;

          Object.keys(icons).forEach((icon) => {
            editor.ui.registry.addIcon(icon, icons[icon]);
          });

          editor.ui.registry.addMenuButton('jxtdbuttons', {
            text: Joomla.JText._('PLG_TINY_CORE_BUTTONS'),
            icon: 'joomla',
            fetch: callback => callback(buttonValues),
          });
          editor.ui.registry.addButton('jstogglebutton', {
            icon: 'toogle_icon',
            tooltip: Joomla.JText._('PLG_TINY_TOGGLE_BUTTONS'),
            onAction: toggleButtonValues.bind(this, editor),
          });
        };
      } else {
        options.setup = (editor) => {
          editor.settings.readonly = readOnlyMode;
        };
      }

      /**
       * After initiate the tinymce editor check if it has hide-menu class
       * If it has hide-menu class then remove first 12 icons
       * @since 4.0.0
       */
      options.init_instance_callback = (editor) => {
        const { parentNode } = editor.editorContainer;
        if (parentNode.classList.contains('joomla-tinymce-hide-menu')) {
          const toxToolbar = editor.editorContainer.querySelector('.tox-toolbar');
          toggleTinymceToolbars(toxToolbar, 12);
        }
      };
      // Create a new instance
      // eslint-disable-next-line no-undef
      const ed = new tinyMCE.Editor(element.id, options, tinymce.EditorManager);
      ed.render();
      /** Register the editor's instance to Joomla Object */
      Joomla.editors.instances[element.id] = {
        // Required by Joomla's API for the XTD-Buttons
        getValue: () => Joomla.editors.instances[element.id].instance.getContent(),
        setValue: text => Joomla.editors.instances[element.id].instance.setContent(text),
        getSelection: () => Joomla.editors.instances[element.id].instance.selection.getContent({ format: 'text' }),
        replaceSelection: text => Joomla.editors.instances[element.id].instance.execCommand('mceInsertContent', false, text),
        // Required by Joomla's API for Mail Component Integration
        disable: disabled => Joomla.editors.instances[element.id].instance.setMode(disabled ? 'readonly' : 'design'),
        // Some extra instance dependent
        id: element.id,
        instance: ed,
        onSave: () => { if (Joomla.editors.instances[element.id].instance.isHidden()) { Joomla.editors.instances[element.id].instance.show(); } return ''; },
      };
      /** On save * */
      document.getElementById(ed.id).form.addEventListener('submit', () => Joomla.editors.instances[ed.targetElm.id].onSave());
    },

  };

  /**
   * Initialize at an initial page load
   */
  document.addEventListener('DOMContentLoaded', () => { Joomla.JoomlaTinyMCE.setupEditors(document); });

  /**
   * Initialize when a part of the page was updated
   */
  document.addEventListener('joomla:updated', event => Joomla.JoomlaTinyMCE.setupEditors(event.target));
})(window.tinyMCE, Joomla, window, document);
