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
                body: JSON.stringify({ playerId, gameId: Number(window.currentGameId ?? 0) })
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
                body: JSON.stringify({ propertyId, gameId: window.currentGameId ?? currentGameId })
            });
            const raw = await res.text();
            console.log("checkPropertyStatus status:", res.status, res.headers.get("content-type"));
            console.log("checkPropertyStatus raw (first 200):", raw.slice(0, 200));
            const prop = JSON.parse(raw);

            if (!prop.success) {
                alert(prop.message || "Failed to fetch property status");
                return;
            }

            // Use buyPropertyProxy to handle the correct buy logic
            const result = await buyPropertyProxy(playerId, propertyId, prop.owner_id, offerPrice);
            if (result?.openModal) return result;

            if (result.success) {
                window.gameProperties[tileIndex].owner_id = Number(playerId);
                syncTileFromMeta(tileIndex);
                updatePropertyUI(tileIndex, result.owned, result.newBalance, playerPanel);
                alert(result.message || "Property successfully bought!");
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
    async function sellProperty(playerPanel, tileIndex) {
        const playerId = playerPanel.dataset.playerId;

        const meta = window.gameProperties?.[tileIndex];
        if (!meta || !meta.id) {
            alert("This tile is not a sellable property.");
            return;
        }

        const propertyId = meta.id;
        const propertyName = meta.name || meta.property_name || ""; // whichever you store

        try {
            const res = await fetch('../../Backend/GameActions/checkPropertyStatus.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ propertyId, gameId: window.currentGameId ?? currentGameId })
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

            const owner = { id: Number(playerId), name: getPlayerNameById(playerId) };
            window.openSellTradeModal(owner, propertyId, propertyName, tileIndex);

        } catch (err) {
            console.error(err);
            alert("Error selling property");
        }
    }

    // Place house or hotel
    async function placeHouseOrHotel(playerPanel, tileIndex, houseCount, hasHotel) {
        const playerId = playerPanel.dataset.playerId;

        const meta = window.gameProperties?.[tileIndex];
        if (!meta || !meta.id) {
            alert("This tile is not a valid property.");
            return;
        }

        const propertyId = meta.id;

        try {
            const res = await fetch('../../Backend/GameActions/PlaceHouseOrHotel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    gameId: Number(window.currentGameId ?? 0),
                    playerId: Number(playerId),
                    propertyId: Number(propertyId),
                    tileIndex: Number(tileIndex)   // 👈 needed for cost
                })
            });

            const result = await res.json();
            return result;

        } catch (err) {
            console.error(err);
            alert("Error placing house/hotel");
            return { success: false };
        }
    }

    async function confirmSellProperty(playerPanel, tileIndex, mode, buyerId = null, sellPrice = null) {
    const playerId = playerPanel.dataset.playerId;

    const meta = window.gameProperties?.[tileIndex];
    if (!meta || !meta.id) {
        return { success: false, message: "Invalid property tile." };
    }
    const propertyId = meta.id;

    try {
        // still verify ownership right before executing
        const res = await fetch('../../Backend/GameActions/checkPropertyStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ propertyId, gameId: window.currentGameId ?? currentGameId })
        });
        const prop = await res.json();
        if (!prop.success) return prop;
        if (String(playerId) !== String(prop.owner_id)) {
            return { success: false, message: "You no longer own this property." };
        }

        const result = await sellPropertyProxy(
        Number(playerId),      // sellerId
        Number(propertyId),
        Number(playerId),      // ownerId (same as seller, since we validated)
        mode,
        buyerId ? Number(buyerId) : null,
        sellPrice ? Number(sellPrice) : null
        );


        if (result.success) {
            const meta = window.gameProperties?.[tileIndex];
            if (meta) {
                meta.owner_id = result.newOwnerId ?? null;
                if (result.newOwnerId == null) { // sold to bank
                meta.house_count = 0;
                meta.hotel_count = 0;
                meta.is_mortgaged = false;
                }
            }
            syncTileFromMeta(tileIndex);
        }


        return result;

    } catch (err) {
        console.error(err);
        return { success: false, message: "Error processing sale" };
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

    function getPlayerNameById(id) {
        const p = (window.playersData || []).find(x => String(x.player_id) === String(id));
        return p?.name || `Player ${id}`;
    }

    function syncTileFromMeta(tileIndex) {
    const meta = window.gameProperties?.[tileIndex];
    if (!meta || !window.tiles?.[tileIndex]) return;

    window.tiles[tileIndex].id = meta.id;
    window.tiles[tileIndex].price = Number(meta.price ?? window.tiles[tileIndex].price ?? 0);
    window.tiles[tileIndex].rent  = Number(meta.rent  ?? window.tiles[tileIndex].rent  ?? 0);

    window.tiles[tileIndex].owner_id = meta.owner_id ?? null;
    window.tiles[tileIndex].house_count = Number(meta.house_count ?? 0);
    window.tiles[tileIndex].hotel_count = Number(meta.hotel_count ?? 0);
    window.tiles[tileIndex].is_mortgaged = !!meta.is_mortgaged;
    }




    return {
        getOutOfJail,
        buyProperty,
        sellProperty,
        confirmSellProperty,
        placeHouseOrHotel
    };
})();

export { GameActionsProxy };
window.GameActionsProxy = GameActionsProxy;