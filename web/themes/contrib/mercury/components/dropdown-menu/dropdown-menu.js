import { ComponentType, ComponentInstance } from '../../lib/component.js';

class DropdownMenu extends ComponentInstance {
  static desktopMQ = window.matchMedia('(width >= 40rem)');

  init() {
    this.resizeTimeout = 0;
    this.resizeFlag = true;

    this.dropdownButtons = Array.from(this.el.querySelectorAll(
      '.dropdown-menu__expand-button'
    ));

    this.topLevelButtons = this.dropdownButtons.filter((button) =>
      button.parentElement.dataset.level == 1
    );

    // Gather first items of second-level and deeper menus for inserting Back buttons.
    this.secondLevelFirstItems = this.el.querySelectorAll('.dropdown-menu__item li:first-child');

    // The Twig file contains a template for our Back buttons, set to `display: none;`.
    this.backButtonTemplate = this.el.nextElementSibling;

    this.setAllMostRoomClasses();

    // Add click listener to expand buttons.
    this.dropdownButtons.forEach(button => {
      button.addEventListener('click', this.toggleSubmenu.bind(this));
    });

    window.addEventListener('resize', this.resizeTasks.bind(this));

    // Close all submenus if the user clicks outside them.
    document.documentElement.addEventListener('click', (event) => {
      if (this.el.contains(event.target)) {
        return;
      }

      this.closeAllSubmenus();
    });

    this.el.classList.add('dropdown-menu--js');
  }
  /**
   * Open a button's submenu.
   * @param {HTMLElement} button - The button whose submenu to open.
   */
  openSubmenu(button) {
    button.classList.add('dropdown-menu__expand-button--expanded');
    button.setAttribute('aria-expanded', 'true');
  }

  /**
   *
   * @param {HTMLElement} button - The button whose submenu to close.
   * @return {undefined}
   */
  closeSubmenu(button) {
    button.classList.remove('dropdown-menu__expand-button--expanded');
    button.setAttribute('aria-expanded', 'false');
  }

  /**
   * Close all submenus.
   */
  closeAllSubmenus() {
    // Don't loop over all the dropdowns if none are expanded.
    if (this.el.querySelector('.dropdown-menu__expand-button--expanded')) {
      this.dropdownButtons.forEach(button => this.closeSubmenu(button));
    }
  }

  /**
   * Determine whether there is more room in the viewport between the element's left edge
   * and the viewport's right, or the viewport's left and the element's right.
   *
   * @param {HTMLElement} el The element to assess and add a class to.
   * @param {string} classPrefix The class to append --left or --right to.
   * @returns {void}
   */
  setMostRoomClass(el, classPrefix) {
    const submenu = el.querySelector(':scope > .dropdown-menu__menu');

    if (!submenu) {
      return;
    }

    const rect = el.getBoundingClientRect();
    const leftClass = classPrefix + 'left';
    const rightClass = classPrefix + 'right';
    const viewportWidth = document.documentElement.clientWidth;

    // The chart at http://developer.mozilla.org/en-US/docs/Web/API/Element/getBoundingClientRect
    // makes it way easier to visualize the math here.
    const leftRoom = rect.right;
    const rightRoom = viewportWidth - rect.right + rect.width;

    // If there's more room to the right, you want left alignment, which makes
    // subitems expand to the right. (And vice versa.)
    if (leftRoom > rightRoom) {
      submenu.classList.remove(leftClass);
      submenu.classList.add(rightClass);
    } else {
      submenu.classList.add(leftClass);
      submenu.classList.remove(rightClass);
    }
  }

  /**
   * Loop over all top-level items and set submenu alignment classes.
   *
   * @returns {void}
   */
  setAllMostRoomClasses() {
    this.el.querySelectorAll('.dropdown-menu__item').forEach(el => {
      this.setMostRoomClass(el, 'dropdown-menu__item--align-');
    });
  };

  /**
   * Collect all resize event tasks for debouncing.
   */
  resizeTasks() {
    // Debounce without delay.
    if (this.resizeFlag === true) {
      this.closeAllSubmenus();
      this.resizeFlag = false;
    }

    window.clearTimeout(this.resizeTimeout);

    this.resizeTimeout = window.setTimeout(
      () => {
        // Debounce with delay.
        this.setAllMostRoomClasses();

        // Reset the non-delayed debounce.
        this.resizeFlag = true;
      },
      250
    );
  };

  /**
   * Function to toggle submenus open and closed via classes on the buttons.
   * @param {Object} buttonClick - Event object
   */
  toggleSubmenu(buttonClick) {
    const button = buttonClick.target.closest('button');

    const submenuWasOpen = button.classList.contains(
      'dropdown-menu__expand-button--expanded'
    );

    button.classList.add('dropdown-menu__expand-button--has-been-opened');

    // On desktop only, allow only one submenu to be open at a time.
    if (DropdownMenu.desktopMQ.matches) {
      const allSameLevelButtons = this.el.querySelectorAll(`.dropdown-menu__expand-button[data-level='${button.dataset.level}']`);
      allSameLevelButtons.forEach(sameLevelButton => this.closeSubmenu(sameLevelButton));
    }

    if (!submenuWasOpen) {
      this.openSubmenu(button);
    } else {
      this.closeSubmenu(button);
    }
  }

  /**
   * Close a parent menu.
   *`
    * @param {MouseEvent} buttonClick Click event.
    */
  closeParentMenu(buttonClick) {
    const backButton = buttonClick.target.closest('button');

    // Traverse from back button → li → ul → li → the previous button.
    this.closeSubmenu(
      backButton
        .closest('ul')
        .closest('li')
        .querySelector('.dropdown-menu__expand-button')
    );
  }
}

window.dropdownMenu = new ComponentType(DropdownMenu, 'dropdownMenu', '.dropdown-menu');
