
define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.slaverevolt", null, {

        /**
         * Hold data about a Spartan Hoplites
         * @param {string} to_city
         * @param {string} cubeid
         */
        constructor: function () {
        },

        /**
         * Create button HTML for the leader's pool.
         * @param {string} player_name 
         * @returns button html
         */
        createSpartaLeaderButton: function(player_name) {
            let lbl = _("${player_name}'s pool");
            lbl = lbl.replace('${player_name}', player_name);
            const button = '<div id="sparta_slaverevolt" class="prk_slaverevolt_btn">'+lbl+'</div>';
            return button;
        },

        /**
         * Create button HTML for a Slave Revolt location.
         * @param {string} id added to button id
         * @param {string} location battle
         * @returns button html
         */
        createButton: function(id, location) {
            const button = '<div id="'+id+'"_slaverevolt" class="prk_slaverevolt_btn">'+location+'</div>';
            return button;
        },

        /**
         * Return an array of Objects referring to stacks where Spartan Hoplites are present.
         * {tile: location, stackid: id}
         * location: battle loc
         * slot: battle_n_hoplite_(att|def)(_ally?)
         * spartans: counters
         */
        getSpartanHopliteLocations: function() {
            const spartans = [];
            // get all the Spartan Hoplites on a battle location
            // iterate through tiles 1-7
            for (let i = 1; i <= 7; i++) {
                const loctile = $('location_'+i).firstChild;
                const locname = loctile.id.split("_")[0];
                const landbattle = 'battle_'+i+'_hoplite_';

                for (const slot of ['att', 'def']) {
                    const hopliteloc = landbattle+slot;
                    for (const hoplitestack of [hopliteloc, hopliteloc+'_ally']) {
                        if (this.hasSpartanHoplites(hoplitestack)) {
                            const spartanObj = this.createStackRef(locname, hoplitestack);
                            spartans.push(spartanObj);
                        }
                    }
                }
            }
            return spartans;
        },

        /**
         * Return an object:
         * {tile: location, stackid: id}
         * @param {string} loc 
         * @param {string} slot 
         * @returns simple Object
         */
        createStackRef: function(loc, slot) {
            return {
                tile: loc,
                stackid: slot,
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