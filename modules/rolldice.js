
const DICE_HTML = '<div id="${side}-die-${die}" class="prk_dice_cube prk_die_${die}">\
                        <div class="prk_die_face" data-face="1" data-side="${side}"></div>\
                        <div class="prk_die_face" data-face="6" data-side="${side}"></div>\
                        <div class="prk_die_face" data-face="4" data-side="${side}"></div>\
                        <div class="prk_die_face" data-face="3" data-side="${side}"></div>\
                        <div class="prk_die_face" data-face="5" data-side="${side}"></div>\
                        <div class="prk_die_face" data-face="2" data-side="${side}"></div>\
                    </div>';

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.dice", null, {

        /**
         * Hold data about a Spartan Hoplites
         * @param {string} to_city
         * @param {string} cubeid
         */
        constructor: function () {
        },

        getDiv: function(die, rollingside) {
            html = DICE_HTML;
            html = html.replaceAll('${die}', die);
            html = html.replaceAll('${side}', rollingside);
            return html;
        },

        /**
         * Places all the dice on the board.
         */
        placeDice: function() {
            // check they aren't already placed
            if (!$('attacker-die-1')) {
                ["attacker", "defender"].forEach(side => {
                    [1, 2].forEach(n => {
                        const diehtml = this.getDiv(n, side);
                        dojo.place(diehtml, $(side+'_dicebox-'+n));
                    });
                });
            }
        },

        /**
         * Remove all the dice
         */
        removeDice: function() {
            const dice = document.getElementsByClassName("prk_dice_cube");
            [...dice].forEach(d => {
                d.remove();
            });
        },

        /**
         * 
         * @param {string} side "attacker" or "defender"
         * @param {string} hit true or false
         */
        highlightResult: function(side, hit) {
            const result = hit ? "hit" : "miss";
            $(side+"_dicebox-1").dataset.result = result;
            $(side+"_dicebox-2").dataset.result = result;
        },

        /**
         * Clear results after a roll
         */
        clearResultHighlights: function() {
            ["attacker", "defender"].forEach(s => {
                delete $(s+"_dicebox-1").dataset.result;
                delete $(s+"_dicebox-2").dataset.result;
            });
        },

        /**
         * Roll the dice with values generated on the server side.
         * @param {string} side "attacker" or "defender"
         * @param {int} val1 
         * @param {int} val2 
         */
        rollDice: function (side, val1, val2) {
            ["1", "2"].forEach(n => {
                const diecube = $(side+"-die-"+n);
                ["prk_die_1", "prk_die_2"].forEach(f => {
                    diecube.classList.toggle(f);
                });
            });
            $(side+"-die-1").dataset.roll = val1;
            $(side+"-die-2").dataset.roll = val2;
        },

    })
});