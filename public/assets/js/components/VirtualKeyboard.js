/**
 * TPT Free ERP - Virtual Keyboard Component
 * Provides a secure virtual keyboard to prevent keyloggers during sensitive input
 */

class VirtualKeyboard {
    constructor() {
        this.currentInput = null;
        this.isShift = false;
        this.isCaps = false;
        this.isSymbols = false;
        this.layout = 'qwerty';
        this.randomize = false;
    }

    /**
     * Generate virtual keyboard HTML and JavaScript
     */
    generateKeyboard(inputId, layout = 'qwerty', options = {}) {
        this.layout = layout;
        this.randomize = options.randomize || false;
        this.theme = options.theme || 'default';
        this.size = options.size || 'medium';

        const keyboardId = 'vk_' + Math.random().toString(36).substr(2, 9);
        const keys = this.getKeyboardLayout(layout, this.randomize);

        const html = this.generateKeyboardHTML(keyboardId, inputId, keys, options);
        const js = this.generateKeyboardJS(keyboardId, inputId, options);

        return html + js;
    }

    /**
     * Get keyboard layout
     */
    getKeyboardLayout(layout, randomize = false) {
        const layouts = {
            qwerty: [
                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
                ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
                ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
                ['z', 'x', 'c', 'v', 'b', 'n', 'm'],
                ['space', 'backspace']
            ],
            numeric: [
                ['1', '2', '3'],
                ['4', '5', '6'],
                ['7', '8', '9'],
                ['0', 'backspace']
            ]
        };

        let keys = layouts[layout] || layouts.qwerty;

        if (randomize) {
            keys = this.randomizeLayout(keys);
        }

        return keys;
    }

    /**
     * Randomize keyboard layout for additional security
     */
    randomizeLayout(keys) {
        const randomized = [];

        for (const row of keys) {
            // Don't randomize special keys
            const specialKeys = ['space', 'backspace', 'shift', 'caps', 'symbols'];
            const regularKeys = row.filter(key => !specialKeys.includes(key));
            const specialKeysInRow = row.filter(key => specialKeys.includes(key));

            // Shuffle regular keys
            this.shuffleArray(regularKeys);

            // Keep special keys in their positions
            randomized.push([...regularKeys, ...specialKeysInRow]);
        }

        return randomized;
    }

    /**
     * Shuffle array (Fisher-Yates algorithm)
     */
    shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    /**
     * Generate keyboard HTML
     */
    generateKeyboardHTML(keyboardId, inputId, keys, options) {
        const classes = ['virtual-keyboard', this.theme, this.size].filter(Boolean).join(' ');

        let html = `<div id="${keyboardId}" class="${classes}">`;

        for (let rowIndex = 0; rowIndex < keys.length; rowIndex++) {
            html += '<div class="keyboard-row">';

            for (const key of keys[rowIndex]) {
                let keyClass = 'keyboard-key';
                let keyText = key;
                let keyValue = key;

                switch (key) {
                    case 'space':
                        keyClass += ' key-space';
                        keyText = 'Space';
                        keyValue = ' ';
                        break;
                    case 'backspace':
                        keyClass += ' key-backspace';
                        keyText = '⌫';
                        keyValue = 'backspace';
                        break;
                    case 'shift':
                        keyClass += ' key-shift';
                        keyText = '⇧';
                        keyValue = 'shift';
                        break;
                    case 'caps':
                        keyClass += ' key-caps';
                        keyText = 'Caps';
                        keyValue = 'caps';
                        break;
                    case 'symbols':
                        keyClass += ' key-symbols';
                        keyText = '!@#';
                        keyValue = 'symbols';
                        break;
                }

                html += `<button type="button" class="${keyClass}" data-key="${keyValue}">${keyText}</button>`;
            }

            html += '</div>';
        }

        html += '</div>';

        return html;
    }

    /**
     * Generate keyboard JavaScript
     */
    generateKeyboardJS(keyboardId, inputId, options) {
        const js = `
        <script>
        (function() {
            const keyboard = document.getElementById('${keyboardId}');
            const input = document.getElementById('${inputId}');

            if (!keyboard || !input) {
                console.warn('Virtual keyboard: Could not find keyboard or input element');
                return;
            }

            // Keyboard state
            const state = {
                shift: false,
                caps: false,
                symbols: false
            };

            // Key mappings
            const keyMappings = {
                shift: {
                    '1': '!', '2': '@', '3': '#', '4': '$', '5': '%', '6': '^', '7': '&', '8': '*', '9': '(', '0': ')',
                    'q': 'Q', 'w': 'W', 'e': 'E', 'r': 'R', 't': 'T', 'y': 'Y', 'u': 'U', 'i': 'I', 'o': 'O', 'p': 'P',
                    'a': 'A', 's': 'S', 'd': 'D', 'f': 'F', 'g': 'G', 'h': 'H', 'j': 'J', 'k': 'K', 'l': 'L',
                    'z': 'Z', 'x': 'X', 'c': 'C', 'v': 'V', 'b': 'B', 'n': 'N', 'm': 'M'
                },
                symbols: {
                    '1': '!', '2': '@', '3': '#', '4': '$', '5': '%', '6': '^', '7': '&', '8': '*', '9': '(', '0': ')',
                    'q': '/', 'w': '?', 'e': '=', 'r': '+', 't': '-', 'y': '_', 'u': '[', 'i': ']', 'o': '{', 'p': '}',
                    'a': '\\\\', 's': '|', 'd': ':', 'f': ';', 'g': '"', 'h': "'", 'j': '<', 'k': '>', 'l': ',',
                    'z': '.', 'x': '\`', 'c': '~', 'v': '^', 'b': '&', 'n': '*', 'm': '('
                }
            };

            // Handle key press
            function handleKeyPress(key) {
                const currentValue = input.value;
                const cursorPos = input.selectionStart || currentValue.length;

                switch (key) {
                    case 'backspace':
                        if (cursorPos > 0) {
                            const beforeCursor = currentValue.substring(0, cursorPos - 1);
                            const afterCursor = currentValue.substring(cursorPos);
                            input.value = beforeCursor + afterCursor;
                            input.selectionStart = input.selectionEnd = cursorPos - 1;
                        }
                        break;
                    case 'space':
                        const beforeSpace = currentValue.substring(0, cursorPos);
                        const afterSpace = currentValue.substring(cursorPos);
                        input.value = beforeSpace + ' ' + afterSpace;
                        input.selectionStart = input.selectionEnd = cursorPos + 1;
                        break;
                    case 'shift':
                        state.shift = !state.shift;
                        updateKeyboardDisplay();
                        break;
                    case 'caps':
                        state.caps = !state.caps;
                        updateKeyboardDisplay();
                        break;
                    case 'symbols':
                        state.symbols = !state.symbols;
                        updateKeyboardDisplay();
                        break;
                    default:
                        let char = key;

                        // Apply shift/caps/symbols transformations
                        if (state.symbols && keyMappings.symbols[key]) {
                            char = keyMappings.symbols[key];
                        } else if (state.shift && keyMappings.shift[key]) {
                            char = keyMappings.shift[key];
                        } else if (state.caps && key.match(/[a-z]/)) {
                            char = key.toUpperCase();
                        }

                        const beforeChar = currentValue.substring(0, cursorPos);
                        const afterChar = currentValue.substring(cursorPos);
                        input.value = beforeChar + char + afterChar;
                        input.selectionStart = input.selectionEnd = cursorPos + 1;

                        // Reset shift after use
                        if (state.shift) {
                            state.shift = false;
                            updateKeyboardDisplay();
                        }
                        break;
                }

                // Trigger input event for any listeners
                input.dispatchEvent(new Event('input', { bubbles: true }));

                input.focus();
            }

            // Update keyboard display
            function updateKeyboardDisplay() {
                const keys = keyboard.querySelectorAll('.keyboard-key');

                keys.forEach(key => {
                    const keyValue = key.dataset.key;

                    // Reset classes
                    key.classList.remove('active');

                    // Apply active states
                    if (keyValue === 'shift' && state.shift) {
                        key.classList.add('active');
                    }
                    if (keyValue === 'caps' && state.caps) {
                        key.classList.add('active');
                    }
                    if (keyValue === 'symbols' && state.symbols) {
                        key.classList.add('active');
                    }

                    // Update key display
                    if (keyValue && keyValue.match(/^[a-z0-9]$/)) {
                        let displayText = keyValue;

                        if (state.symbols && keyMappings.symbols[keyValue]) {
                            displayText = keyMappings.symbols[keyValue];
                        } else if (state.shift && keyMappings.shift[keyValue]) {
                            displayText = keyMappings.shift[keyValue];
                        } else if (state.caps) {
                            displayText = keyValue.toUpperCase();
                        }

                        key.textContent = displayText;
                    }
                });
            }

            // Add click handlers
            keyboard.addEventListener('click', function(e) {
                if (e.target.classList.contains('keyboard-key')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const key = e.target.dataset.key;
                    handleKeyPress(key);
                }
            });

            // Prevent context menu on keyboard
            keyboard.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            // Auto-focus input when keyboard is clicked
            keyboard.addEventListener('mousedown', function(e) {
                e.preventDefault();
                input.focus();
            });

            // Initialize display
            updateKeyboardDisplay();

            // Log keyboard usage for security monitoring
            if (typeof API !== 'undefined' && API.logSecurityEvent) {
                API.logSecurityEvent('virtual_keyboard_used', {
                    input_field: '${inputId}',
                    layout: '${this.layout}',
                    randomized: ${this.randomize}
                });
            }

        })();
        </script>
        `;

        return js;
    }

    /**
     * Generate secure token for keyboard session
     */
    static generateSessionToken() {
        return Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
    }

    /**
     * Validate keyboard session
     */
    static validateSession(token) {
        return typeof token === 'string' && token.length >= 10;
    }
}

// Register component
if (typeof ComponentRegistry !== 'undefined') {
    ComponentRegistry.register('VirtualKeyboard', VirtualKeyboard);
}

// Make globally available
if (typeof window !== 'undefined') {
    window.VirtualKeyboard = VirtualKeyboard;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VirtualKeyboard;
}
