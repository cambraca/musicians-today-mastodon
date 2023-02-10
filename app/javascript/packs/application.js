import './public-path';
import main from "mastodon/main"

import { start } from '../mastodon/common';
import { loadLocale } from '../mastodon/locales';
import { loadPolyfills } from '../mastodon/polyfills';

start();

loadPolyfills()
  .then(loadLocale)
  .then(main)
  .then(async () => {
    // Very hacky code for changing the mascot every once in a while.
    async function changeMascot() {
      if (!window.mascots)
        window.mascots = await (await fetch('https://musicians.today/mascots.json')).json();

      const index = Math.floor(Math.random() * window.mascots.length);
      document.querySelector('.drawer__inner__mastodon img').setAttribute('src', window.mascots[index]);
    }
    setInterval(changeMascot, 1000*60*60);
    window.addEventListener('load', changeMascot);
  })
  .catch(e => {
    console.error(e);
  });
