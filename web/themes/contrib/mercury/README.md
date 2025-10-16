# Mercury Theme

**Mercury** is a modern and flexible Drupal theme designed to help developers quickly build scalable and efficient websites. It utilizes cutting-edge tools such as Vite, Storybook, and the Starshot Design System to create a seamless development experience.

## Features

- **Vite**: A fast and modern build tool for web development, providing lightning-fast hot module replacement (HMR) and optimized production builds.
- **Storybook**: Automatically generates stories for components, enabling UI development in isolation and ensuring consistency across components.
- **Starshot Design System**: A design system used to maintain consistent UI elements and improve design-to-development workflows.n

## Installation

To install the theme, follow these steps:

1. Clone the repository into your Drupal themes directory:
   `git clone <repository-url> themes/custom/mercury`
2. Make sure you are using the correct node version
   `nvm use'
3. Install the required dependencies using pnpm:
   `pnpm install`
4. Enable the theme in Drupal:
   `drush theme:enable mercury`
5. (Optional) If you want to run Storybook locally for component development, you can use the following command:
   `pnpm run storybook`

---

## Storybook Generator Vite Plugin

This Vite plugin automatically generates Storybook stories for your components based on their YAML metadata.

## Installation

1. Make sure you have the required dependencies:

```bash
npm install glob --save-dev
```

2. Copy the `vite-plugin-storybook-generator.js` file to your project root or plugins directory.

## Usage

Add the plugin to your `vite.config.js` file:

```javascript
import { defineConfig } from 'vite';
import storybookGenerator from './vite-plugin-storybook-generator';

export default defineConfig({
  plugins: [
    storybookGenerator({
      // Optional: override default options
      componentsDir: 'components', // Default directory containing components
      forceOverwrite: false, // Whether to overwrite existing story files
    }),
    // Your other plugins...
  ],
});
```

## How It Works

The plugin:

1. Scans the components directory for component folders
2. For each component, checks if it has the required files:
   - `[component-name].component.yml` - YAML metadata file
   - `[component-name].twig` - Twig template file
   - `[component-name].css` - CSS file (optional)
   - `[component-name].js` - JavaScript file (optional, imported if exists)
3. Generates a Storybook story file (`[component-name].stories.js`) that:
   - Imports the component's YAML metadata, Twig template, and CSS
   - Conditionally imports the component's JavaScript file if it exists
   - Uses the `generateArgTypesAndArgs` helper to generate Storybook args and argTypes
   - Sets up the story with the Default export using the `twingStory` helper

## Component Structure

The plugin expects components to follow this file structure:

```
components/
├── component-name/
│   ├── component-name.component.yml
│   ├── component-name.twig
│   ├── component-name.css
│   ├── component-name.js (optional)
│   └── component-name.stories.js (will be generated)
```

## Options

- `componentsDir` (string): Path to the directory containing component folders (default: 'components')
- `forceOverwrite` (boolean): Whether to overwrite existing story files (default: false)

## Notes

- The plugin runs during the Vite build process
- It will log information about generated stories and any errors
- If `forceOverwrite` is set to `false`, it will skip components that already have a story file

## Storybook: Variants and custom data

Components may have a `component-name.storybook.yml` file with arbitrary data, which will be available in its Twig files as a top-level `storybook` variable.

Components may also have additional Twig files for variants of the main component. Any file named like `component-name~variant-name.twig` will show up as a variant nested under the main component. (Note the tilde (~) separating the component name from the variable name.) If you wish for one of your variants to replace the main component Twig altogether in Storybook, do two things:

- Add a component-name.storybook.yml file, with `hide_main: true` as a top-level property
- Name your variant file `component-name~main.twig`.

You can see all of the above in action in the Collapsible Section component.

## Component JavaScript

`lib/component.js` has two classes you can use to nicely encapsulate your component JS without pasting all the `Drupal.behaviors.componentName` boilerplate into every file. The steps are:

1. Extend the `ComponentInstance` class to a new class with the code for your component.
2. Create a new instance of the `ComponentType` class to automatically activate all the component instances on that page.

For example, here's a stub of `collapsible-section.js`:

```js
import { ComponentType, ComponentInstance } from '../../lib/component.js';

// Make a new class with the code for our component.
//
// In every method of this class, `this.el` is an HTMLElement object of
// the component container, whose selector you provide below. You don't
// have an array of elements that you have to `.forEach()` over yourself;
// the ComponentType class handles all that for you.
class CollapsibleSection extends ComponentInstance {
  // Every subclass must have an `init` method to activate the component.
  init() {
    this.el
      .querySelector('.collapsible-section__content')
      .classList.toggle('visible');
    this.el.addClass('js');
  }

  // You may also implement a `remove()` method to clean up when a component is
  // about to be removed from the document. This will be invoked during the
  // `detach()` method of the Drupal behavior.

  // You can create as many other methods as you want; in all of them,
  // `this.el` represents the single instance of the component. Any other
  // properties you add to `this` will be isolated to that one instance
  // as well.
}

// Now we instantiate ComponentType to find the component elements and run
// our script.
new ComponentType(
  // First argument: The subclass of ComponentInstance we just created above.
  CollapsibleSection,
  // Second argument: A camel-case unique ID for the behavior (and for `once()`
  // if applicable).
  'collapsibleSection',
  // Third argument: A selector for `querySelectorAll()`. All matching elements
  // on the page get their own instance of the subclass you created, each of
  // which has `this.el` pointing to one of those matches.
  '.collapsible-section'
);
```

This is all the code required to be in each component. The ComponentType instance handles finding the elements, running them through `once` if available, and either running them immediately in Storybook or adding them to `Drupal.behaviors`.

All the objects created this way will be stored in a global variable so you can do stuff with them later. Since the `namespace` variable at the top of component.js is `mercuryComponents`, you would find the Collapsible Section's ComponentType instance at `window.mercuryComponents.collapsibleSection`.

Furthermore, `window.mercuryComponents.collapsibleSection.instances` is an array of all the ComponentInstance objects, and `window.mercuryComponents.collapsibleSection.elements` is an array of all the component container elements.

## Troubleshooting

**If XB throws a fatal error, use this comment to reset the page**
`ddev drush sql:query "delete from key_value_expire where collection='tempstore.shared.experience_builder.auto_save'"`
