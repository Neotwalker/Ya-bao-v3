import { initRelatedArticlesSwipers } from './components/related-articles-swiper.js';
import { runInitializers } from './utils/init.js';

function initArticlePage() {
  runInitializers(
    initRelatedArticlesSwipers,
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initArticlePage, { once: true });
} else {
  initArticlePage();
}
