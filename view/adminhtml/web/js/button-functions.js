require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'prototype',
    'loader'
], function ($, modal) {

    /**
     * @param{String} modalSelector - modal css selector.
     * @param{Object} options - modal options.
     */
    function initModal(modalSelector, options) {

        if (!$(modalSelector).length) return;

        let defualtOptions = {
            modalClass: 'tl-modal',
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: options.title || '',
            buttons: [
                {
                    text: $.mage.__('Close'),
                    class: 'action tl-btn',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        };

        // Additional buttons for downloading
        if (options.buttons) {
            let additionalButtons =                 {
                text: $.mage.__('Download'),
                class: 'tl-btn tl-btn-secondary',
                click: () => {
                    let elText = document.getElementById(`tl-result_${options.buttons}`).innerText || '',
                        link = document.createElement('a');

                    link.setAttribute('download', `${options.buttons}-log.txt`);
                    link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                    link.click();
                },
            };

            defualtOptions.buttons.unshift(additionalButtons);
        }

        modal(defualtOptions, $(modalSelector));
        $(modalSelector).loader({texts: ''});
    }

    var successHandlers = {
        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        logs: function (data, $container, action) {
            let blockClass = action === 'debug' ? 'result' : 'error',
                result = data;

            // debugger;
            if (Array.isArray(data)) {
                result = document.createElement('ul');

                for(let i = 0; i < data.length; i++) {
                    const   LI = document.createElement('li'),
                            STRONG = document.createElement('strong'),
                            P = document.createElement('p');

                    LI.classList.add(`tl-ui-${blockClass}_debug-item`);
                    STRONG.textContent = data[i].date;
                    P.textContent = data[i].msg;

                    LI.appendChild(STRONG);
                    LI.appendChild(P);
                    result.appendChild(LI);
                }

                $container[0].querySelector('.result').appendChild(result);
            } else {
                $container[0].querySelector('.result').textContent = result;
            }
        },
    }

    // init debug modal
    $(() => {
        initModal('#tl-result_debug-modal', { title: $.mage.__('last 100 debug log lines'), buttons: 'debug' });
        initModal('#tl-result_error-modal', { title: $.mage.__('last 100 error log records'), buttons: 'error' });
    });

    /**
     * Ajax request event
     */
    $(document).on('click', '[id^=truelayer-button]', function () {
        var actionName = this.id.split('_')[1];
        var $modal = $(`#tl-result_${actionName}-modal`);
        var $result = $(`#tl-result_${actionName}`);

        $modal.modal('openModal').loader('show');
        $result.hide();

        new Ajax.Request($modal.data('tl-endpoind-url'), {
            loaderArea: false,
            asynchronous: true,
            onSuccess: function (response) {
                if (response.status > 200) {
                    var result = response.statusText;
                } else {
                    successHandlers['logs'](response.responseJSON.result || response.responseJSON, $result, actionName);
                    $result.fadeIn();
                    $modal.loader('hide');
                }
            }
        });
    });
});
