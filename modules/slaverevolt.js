
define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.slaverevolt", null, {

        /**
         * Hold data about a cube being moved with the Alkibiades special action.
         * @param {string} to_city
         * @param {string} cubeid
         */
        constructor: function () {
        },

        /**
         * Return an array of Objects.
         * location: battle loc
         * slot: battle_n_hoplite_(att|def)(_ally?)
         * spartans: counters
         */
        getSpartanHoplites: function() {
            const spartans = [];
            // get all the Spartan Hoplites on a battle location
            for (let i = 1; i <= 7; i++) {
                const loctile = $('location_'+i).firstChild;
                const locname = loctile.id.split("_")[0];

                const battle = 'battle_'+i+'_hoplite_';

                for (const slot of ['att', 'def']) {
                    const hopliteloc = battle+slot;
                    for (const hoplitestack of [hopliteloc, hopliteloc+'_ally']) {
                        if (this.hasSpartanHoplites(hoplitestack)) {
                            const spartanObj = this.createSpartans(locname, hoplitestack);
                            spartans.push(spartanObj);
                        }
                    }
                }
            }
            return spartans;
        },

        /**
         * location, slot
         * @param {string} loc 
         * @param {string} slot 
         * @returns simple Object
         */
        createSpartans: function(loc, slot) {
            return {
                location: loc,
                slot: slot,
            };
        },

        /**
         * Check whether any Spartan Hoplites are present at this battle slot.
         * @param {string} id 
         * @return true if any Hoplite stacks
         */
        hasSpartanHoplites: function(loc_id) {
            const hoplites = $(loc_id).children;
            for (const h of hoplites) {
                if (h.id.split("_")[0] == "sparta") {
                    return true;
                }
            }
            return false;
        },

    })
});