require([
    'jquery',
    'mage/translate',
    '!domReady'
], function ($, $t) {

    const COMMENT = Array.from($('form[action*="truelayer"] .tl-heading-comment'));

    if(COMMENT.length) {
        COMMENT.forEach((item) => {
            let showMoreLessBtnHtml = document.createElement("div"),
                SPAN = document.createElement('span');

            showMoreLessBtnHtml.classList.add('tl-show-more-actions');
            SPAN.classList.add('tl-show-btn-more');
            SPAN.textContent = $t('Show more.');

            showMoreLessBtnHtml.appendChild(SPAN);
            item.parentElement.appendChild(showMoreLessBtnHtml);
            checkShowMoreVisibility(item);
        });

        $(document).on('click', '.tl-show-more-actions span', (e) => {
            let button = e.target,
                parent = e.target.closest('.value').querySelector('.tl-heading-comment');

            if (parent.classList.contains('show')) {
                parent.classList.remove('show');
                button.textContent = $t('Show more.');
            } else {
                parent.classList.add('show');
                button.textContent = $t('Show less.');
            }
        });

        window.onresize = () => {
            COMMENT.forEach((item) => checkShowMoreVisibility(item));
        }
    }

    // Check if 'Show more' need to display
    function checkShowMoreVisibility(text) {
        let sHeight = text.scrollHeight,
            cHeight = text.clientHeight,
            button  = text.parentElement.querySelector('.tl-show-more-actions');

        cHeight >= sHeight ? button.classList.add('hidden') : button.classList.remove('hidden');
    }
});
