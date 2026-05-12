// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript for download responses event.
 *
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    const t = {

        /**
         * CSS Selector.
         */
        SELECTOR: {
            ATTEMPT_CHECKBOX: '[name="questionusageid[]"]',
            BUTTON_DOWNLOAD_SELECT: '#downloadselectedattemptsbutton',
            BUTTON_DOWNLOAD_ALL: '#downloadallattemptsbutton',
            BUTTON_DELETE: '#deleteattemptsbutton'
        },

        /**
         * Initialise function
         */
        init: function() {
            let totalDownloadChecked = 0;
            let totalDeleteChecked = 0;
            let allowDownloadAll = 0;

            $(t.SELECTOR.ATTEMPT_CHECKBOX).each(function() {
                let allowDownload = t.isAllowDownloading(this);
                if (allowDownload) {
                    allowDownloadAll = true;
                }
            });

            $(t.SELECTOR.BUTTON_DOWNLOAD_ALL).prop('disabled', !allowDownloadAll);

            $(t.SELECTOR.ATTEMPT_CHECKBOX).change(function() {
                let allowDownload = t.isAllowDownloading(this);
                if (allowDownload) {
                    if (this.checked) {
                        totalDownloadChecked++;
                    } else {
                        totalDownloadChecked--;
                    }
                }
                let allowDelete = t.isAllowDeleting(this);
                if (allowDelete) {
                    if (this.checked) {
                        totalDeleteChecked++;
                    } else {
                        totalDeleteChecked--;
                    }
                }
                $(t.SELECTOR.BUTTON_DOWNLOAD_SELECT).prop('disabled', !(totalDownloadChecked > 0));
                $(t.SELECTOR.BUTTON_DELETE).prop('disabled', !(totalDeleteChecked > 0));
            });
        },

        /**
         * Check that the element is allow to download or not
         * @param {jquery} element Element to check
         */
        isAllowDownloading: function(element) {
            let checkBox = $(element);
            let questionUsageMeta = checkBox.val().split('-');
            return questionUsageMeta[2] == 1;
        },

        /**
         * Check that the element is allow to delete or not
         * @param {jquery} element Element to check
         */
        isAllowDeleting: function(element) {
            let checkBox = $(element);
            let questionUsageMeta = checkBox.val().split('-');
            return questionUsageMeta[3] == 1;
        }
    };

    return t;
});
