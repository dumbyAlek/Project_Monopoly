// GameActionsProxy.js
import { buyPropertyProxy } from '../../Backend/GameActions/BuyPropertyProxy.js';
import { sellPropertyProxy } from '../../Backend/GameActions/SellPropertyProxy.js';

const GameActionsProxy = (() => {

    // Get out of jail
    async function getOutOfJail(playerPanel) {
        const playerId = playerPanel.dataset.playerId;

        try {
            const res = await fetch('../../Backend/GameActions/getOutOfJail.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ playerId })
            });
            const result = await res.json();

            if (!result.success) {
                alert(result.message || "Cannot get out of jail");
                return;
            }

            // Update front-end state from DB
            playerPanel.dataset.inJail = result.is_in_jail ? "true" : "false";
            playerPanel.querySelector(".money-value").textContent = result.money;

            return result;
        } catch(err) {
            console.error(err);
            alert("Error in getOutOfJail");
        }
    }

    // Buy property
    async function buyProperty(playerPanel, tileIndex, offerPrice = null) {
        const playerId = playerPanel.dataset.playerId;

        // ✅ tileIndex -> real DB property_id
        const meta = window.gameProperties?.[tileIndex];
        if (!meta || !meta.id) {
            alert("This tile is not a purchasable property.");
            return;
        }
        const propertyId = meta.id;


        try {
            // Call backend to get property info (owner, price)
            const res = await fetch('../../Backend/GameActions/checkPropertyStatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ propertyId })
            });
            const prop = await res.json();

            if (!prop.success) {
                alert(prop.message || "Failed to fetch property status");
                return;
            }

            // Use buyPropertyProxy to handle the correct buy logic
            const result = await buyPropertyProxy(playerId, propertyId, prop.owner_id, offerPrice);

            if (result.success) {
                updatePropertyUI(tileIndex, result.owned, result.newBalance, playerPanel);
            } else {
                alert(result.message || "Failed to buy property");
            }

            return result;

        } catch(err) {
            console.error(err);
            alert("Error buying property");
        }
    }

    // Sell property
    async function sellProperty(playerPanel, tileIndex, sellPrice = null) {
        const playerId = playerPanel.dataset.playerId;

        const meta = window.gameProperties?.[tileIndex];
        if (!meta || !meta.id) {
            alert("This tile is not a sellable property.");
            return;
        }
        const propertyId = meta.id;

        try {

            // Call backend to get property info (owner)
            const res = await fetch('../../Backend/GameActions/checkPropertyStatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ propertyId })
            });
            const prop = await res.json();

            if (!prop.success) {
                alert(prop.message || "Cannot fetch property info");
                return;
            }

            
            if (String(playerId) !== String(prop.owner_id)) {
                alert("You do not own this property.");
                return;
            }

            // Use sellPropertyProxy to handle correct sell logic
            const result = await sellPropertyProxy(playerId, propertyId, prop.owner_id, sellPrice);

            if (result.success) {
                updatePropertyUI(tileIndex, result.owned, result.newBalance, playerPanel);
            } else {
                alert(result.message || "Cannot sell property");
            }

            return result;

        } catch(err) {
            console.error(err);
            alert("Error selling property");
        }
    }

    function updatePropertyUI(tileIndex, owned, newBalance, playerPanel) {
    if (newBalance !== undefined) {
        playerPanel.querySelector(".money-value").textContent = newBalance;
    }

    const tileId = window.mappingLabels[tileIndex];
    const buyBtn = document.querySelector(`#${tileId} .buy-btn`);
    const sellBtn = document.querySelector(`#${tileId} .sell-btn`);

    if (buyBtn) buyBtn.disabled = owned;
    if (sellBtn) sellBtn.disabled = !owned;
    }

    return {
        getOutOfJail,
        buyProperty,
        sellProperty
    };
})();

export { GameActionsProxy };