
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
                const adie1 = this.getDiv(1, "attacker");
                const adie2 = this.getDiv(2, "attacker");
                const ddie1 = this.getDiv(1, "defender");
                const ddie2 = this.getDiv(2, "defender");
                dojo.place(adie1, $('attacker_dice1'));
                dojo.place(adie2, $('attacker_dice2'));
                dojo.place(ddie1, $('defender_dice1'));
                dojo.place(ddie2, $('defender_dice2'));
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
            const result = (hit) ? "hit" : "miss";
            $(side+"-die-1").dataset.result = result;
            $(side+"-die-2").dataset.result = result;
        },

        /**
         * Clear results after a roll
         */
        clearResultHighlights: function() {
            delete $("attacker-die-1").dataset.result;
            delete $("attacker-die-2").dataset.result;
            delete $("defender-die-1").dataset.result;
            delete $("defender-die-2").dataset.result;
        },

        /**
         * Roll the dice with values generated on the server side.
         * @param {string} side "attacker" or "defender"
         * @param {int} val1 
         * @param {int} val2 
         */
        rollDice: function (side, val1, val2) {
            [$(side+"-die-1"), $(side+"-die-2")].forEach(die => {
                ["prk_die_1", "prk_die_2"].forEach(f => {
                    die.classList.toggle(f);
                });
            });
            $(side+"-die-1").dataset.roll = val1;
            $(side+"-die-2").dataset.roll = val2;
        },

    })
});