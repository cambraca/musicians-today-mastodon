import './public-path';
import loadPolyfills from '../mastodon/load_polyfills';
import { start } from '../mastodon/common';

start();

loadPolyfills().then(async () => {
  const { default: main } = await import('mastodon/main');

  // Very hacky code for changing the mascot every once in a while.
  async function changeMascot() {
    if (!window.mascots)
      window.mascots = await (await fetch("https://musicians.today/mascots.json")).json();

    const index = Math.floor(Math.random() * window.mascots.length);
    document.querySelector('.drawer__inner__mastodon img').setAttribute('src', window.mascots[index]);
  }
  setInterval(changeMascot, 1000*60*60);
  window.addEventListener('load', changeMascot);

  return main();
}).catch(e => {
  console.error(e);
});
