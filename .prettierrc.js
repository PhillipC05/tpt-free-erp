module.exports = {
  // Basic formatting options
  printWidth: 120,
  tabWidth: 2,
  useTabs: false,
  semi: true,
  singleQuote: true,
  quoteProps: 'as-needed',
  trailingComma: 'none',
  bracketSpacing: true,
  bracketSameLine: false,
  arrowParens: 'avoid',

  // Language-specific overrides
  overrides: [
    {
      files: '*.json',
      options: {
        printWidth: 200
      }
    },
    {
      files: '*.md',
      options: {
        printWidth: 100,
        proseWrap: 'preserve'
      }
    },
    {
      files: ['*.html', '*.php'],
      options: {
        printWidth: 200,
        singleQuote: false,
        trailingComma: 'none'
      }
    },
    {
      files: ['*.css', '*.scss', '*.less'],
      options: {
        printWidth: 120,
        singleQuote: false
      }
    },
    {
      files: ['*.yml', '*.yaml'],
      options: {
        printWidth: 120,
        singleQuote: false,
        tabWidth: 2
      }
    }
  ],

  // Plugin configurations
  plugins: [],

  // Special file handling
  endOfLine: 'lf',

  // Embedded language formatting
  embeddedLanguageFormatting: 'auto',

  // HTML-specific options
  htmlWhitespaceSensitivity: 'css',

  // Vue.js options (if using Vue)
  vueIndentScriptAndStyle: false,

  // JSX options
  jsxSingleQuote: true,
  jsxBracketSameLine: false,

  // Range formatting (for partial formatting)
  rangeStart: 0,
  rangeEnd: Infinity,

  // File path patterns to ignore
  filepath: null,

  // Require pragma for formatting
  requirePragma: false,

  // Insert pragma at top of formatted files
  insertPragma: false
};
