
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
         * 
         * 
         */
        getSpartanHopliteLocs: function() {
            const spartans = [];
            for (let i = 1; i <= 7; i++) {
                const loctile = $('location_'+i).firstChild;
                const locname = loctile.id.split("_")[0];

                const battle = 'battle_'+i+'_hoplite_';

                for (const slot of ['att', 'def']) {
                    const hopliteloc = battle+slot;
                    for (const hoplitestack of [hopliteloc, hopliteloc+'_ally']) {
                        const hoplites = this.getSpartanHoplites(hoplitestack);
                        if (hoplites.length > 0) {
                            const spartanObj = this.createSpartans(locname, hoplitestack, hoplites);
                            spartans.push(spartanObj);
                        }
                    }
                }
            }
            return spartans;
        },

        /**
         * location, slot, spartans
         * @param {string} loc 
         * @param {string} slot 
         * @param {DOMelements} counters 
         * @returns simple Object
         */
        createSpartans: function(loc, slot, counters) {
            return {
                location: loc,
                slot: slot,
                spartans: counters
            };
        },

        /**
         * Check whether any Spartan Hoplites are present at this battle slot.
         * @param {string} id 
         * @return array of counters (may be empty)
         */
        getSpartanHoplites: function(loc_id) {
            const spartans = [];
            const hoplites = $(loc_id).children;
            for (const h of hoplites) {
                if (h.id.split("_")[0] == "sparta") {
                    spartans.push(h);
                }
            }
            return spartans;
        },

    })
});