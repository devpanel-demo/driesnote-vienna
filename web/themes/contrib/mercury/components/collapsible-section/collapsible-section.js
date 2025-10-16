import { ComponentType, ComponentInstance } from '../../lib/component.js';
import currentlyInCanvasEditor from '../../lib/currentlyInCanvasEditor.js';

class CollapsibleSection extends ComponentInstance {
  // An internal private property to keep up with the current state of the
  // accordion. The hash makes it so you can't get or set this property outside
  // of this file.
  #savedAsOpen;

  static openClass = 'collapsible-section--open';
  static animateClass = 'collapsible-section--animate';

  // Whether ancestor accordion containers should close other collapsibles when
  // this one is opened.
  shouldDispatchEvents = true;

  init() {
    if (currentlyInCanvasEditor()) {
      return;
    }

    // Save our togglable classes for easy reference.
    this.button = this.el.querySelector('.collapsible-section--title');
    this.contentContainer = this.el.querySelector(
      '.collapsible-section--content'
    );
    this.focusableDescendants = this.contentContainer.querySelectorAll(':is(input, select, textarea, button, object):not(:disabled), a:is([href]), [tabindex]');

    // Keep track of the starting tabindex for all focusable descendants, so we
    // can restore them after nuking them when the collapsible is closed.
    this.focusableDescendants.forEach(el => {
      el.tabIndex = el.tabIndex || 0;
      el.dataset.originalTabIndex = el.tabIndex;
    });

    // With the `set isOpen()` below, merely setting this property does all the
    // stuff necessary to open or close the collapsible.
    this.isOpen = this.el.dataset.openByDefault === 'true';

    // Figure out what height the content will be when open so we can smoothly
    // animate to it with CSS.
    this.measureNaturalHeight();

    // The previous line enables animations, but we're not ready for them yet.
    this.el.classList.remove(CollapsibleSection.animateClass);

    // Remeasure the height on every (debounced) resize event.
    let timeout = 0;

    window.addEventListener('resize', (e) => {
      this.el.classList.add('collapsible-section--resizing');
      window.clearTimeout(timeout);
      timeout = window.setTimeout(() => {
        this.measureNaturalHeight();
        this.el.classList.remove('collapsible-section--resizing');
      }, 350);
    });

    // Make the button work.
    this.button.addEventListener('click', () => {
      // Toggle the collapsible.
      this.isOpen = !this.isOpen;
    });

    this.el.classList.add('collapsible-section--js');
    void this.el.offsetHeight;
    this.el.classList.add(CollapsibleSection.animateClass);
  }

  // This setter makes it so the collapsible can be opened and closed just by
  // doing `this.isOpen = true` or `this.isOpen = false` rather than calling a
  // method. The advantage is that (for example) if you have a boolean variable
  // `shouldOpen`, you can just do `this.isOpen = shouldOpen` rather than all
  // this:
  //
  // ```js
  // if(shouldOpen) {
  //   this.open();
  // } else {
  //   this.close();
  // }
  // ```Even
  set isOpen(val) {
    if (val) {
      // First do all the DOM manipulation needed to actually open the
      // collapsible.
      this.el.classList.add(CollapsibleSection.openClass);
      this.focusableDescendants.forEach(el => {
        el.tabIndex = el.dataset.originalTabIndex
      });
      this.button.setAttribute('aria-expanded', 'true');

      // Then stash the current state in a simple private property with no
      // getters or setters involved.
      this.#savedAsOpen = true;

      // Dispatch an event that any accordion container ancestors can use to
      // close other collapsibles.
      if (this.shouldDispatchEvents) {
        this.el.dispatchEvent(new Event('collapsibleopen', { bubbles: true }));
      }
    } else {
      // DOM manipulation.
      this.el.classList.remove(CollapsibleSection.openClass);
      this.focusableDescendants.forEach(el => {
        el.tabIndex = -1;
      });
      this.button.setAttribute('aria-expanded', 'false');
      // Stash current state.
      this.#savedAsOpen = false;
    }
  }

  // Get the simple boolean we saved in the setter.
  get isOpen() {
    return this.#savedAsOpen;
  }

  // Measure how tall the content should be when open so we can smoothly animate
  // to it using CSS.
  measureNaturalHeight() {
    // What we do here should not be seen by ancestor accordions.
    this.shouldDispatchEvents = false;
    // Remember what state the collapsible started in.
    const previousState = this.isOpen;
    // Turn off animations.
    this.el.classList.remove(CollapsibleSection.animateClass);
    // Open the collapsible if it's not already open.
    this.isOpen = true;
    // Measure the natural height and make it available to CSS as a custom
    // property.
    const height = this.contentContainer.getBoundingClientRect().height;
    this.el.style.setProperty('--natural-height', `${height}px`);
    // Restore the collapsible to the state it started in.
    this.isOpen = previousState;
    // Re-enable animations.
    this.el.classList.add(CollapsibleSection.animateClass);
    // Become visible to ancestor accordions again.
    this.shouldDispatchEvents = true;
  }
}

new ComponentType(
  CollapsibleSection,
  'collapsibleSection',
  '.collapsible-section'
);
