import { ComponentType, ComponentInstance } from '../../lib/component.js';

class Navbar extends ComponentInstance {
  #savedAsOpen = false;

  init() {
    this.closeButton = this.el.querySelector('.navbar--hide-menu');
    this.menuButton = this.el.querySelector('.navbar--hamburger');
    this.menu = this.el.querySelector('.navbar--menu');

    this.menuButton.addEventListener('click', () => {
      this.menu.querySelectorAll('.dropdown-menu__expand-button--has-been-opened').forEach(button => {
        button.classList.remove('dropdown-menu__expand-button--has-been-opened');
      });
      this.isOpen = true;
    })

    this.closeButton.addEventListener('click', () => {
      this.isOpen = false;
    })
  }

  set isOpen(value) {
    if (value) {
      this.menu.style.display = 'flex';
      this.menu.querySelector('a, button').focus();
    } else {
      this.menu.style.display = '';
    }

    this.#savedAsOpen = !!value;
  }

  get isOpen() {
    return this.#savedAsOpen;
  }


}

window.navbar = new ComponentType(Navbar, 'navbar', '.navbar');
