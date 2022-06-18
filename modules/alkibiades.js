
define(["dojo", "dojo/_base/declare"], function (dojo, declare) {
    return declare("perikles.alkibiades", null, {
        /**
         * Hold data about a cube being moved with the Alkibiades special action.
         * @param {string} to_city
         * @param {string} cubeid
         */
        constructor: function () {
            this.player_id = null;
            this.to_city = null;
            this.from_city = null;
        },

        /**
         * Player id whose cube is being moved.
         * @returns string
         */
        player: function() {
            return this.player_id;
        },

        /**
         * City cube is being removed from
         * @returns string
         */
        from: function() {
            return this.from_city;
        },

        /**
         * City cube is being moved to
         * @returns string
         */
        to: function() {
            return this.to_city;
        },

        /**
         * From a cube, split its ID string and assign the from and player_id
         * @param {string} cube
         */
        setValues: function(cube) {
            const [selected_pid, fromcity] = cube.id.split('_').splice(0, 2);
            this.player_id = selected_pid;
            this.from_city = fromcity;
        },

        /**
         * 
         * @param {string} tocity
         */
        setToCity: function(tocity) {
            this.to_city = tocity;
        },
    });
});