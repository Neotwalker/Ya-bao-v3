import { initHeader } from './components/header.js';
import { initHeroMotion } from './components/hero-motion.js';
import { initModal } from './components/modal.js';

document.documentElement.classList.add('js');

try { initHeader(); } catch (error) { console.error('Header init failed:', error); }
try { initHeroMotion(); } catch (error) { console.error('Hero motion init failed:', error); }
try { initModal(); } catch (error) { console.error('Modal init failed:', error); }
