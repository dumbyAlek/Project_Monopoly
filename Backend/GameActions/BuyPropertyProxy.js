// BuyPropertyProxy.js
export const buyPropertyProxy = async (buyerId, propertyId, ownerId = null, offerPrice = null) => {
    try {
        let url = '';
        let payload = {
        playerId: Number(buyerId),
        propertyId: Number(propertyId)
        };
        // let payload = {
        // playerId: buyerId,
        // propertyId: propertyId
        // };


        if (ownerId === null) {
            // Property is unowned → buy from bank
            url = '../../Backend/GameActions/buyUnownedProperty.php';
            if (offerPrice) payload.offerPrice = offerPrice;
        } else if (String(ownerId) !== String(buyerId)) {

            // If no offerPrice yet -> open modal and STOP here (no backend call)
            if (!offerPrice || Number(offerPrice) <= 0) {
                // find tileIndex from window.gameProperties (keyed by tileIndex)
                const tileIndex = Object.keys(window.gameProperties || {}).find(
                    k => String(window.gameProperties[k]?.id) === String(propertyId)
                );

                // build buyer/owner objects for modal display (uses window.playersData)
                const buyerObj = {
                    id: buyerId,
                    name: (window.playersData || []).find(p => String(p.player_id) === String(buyerId))?.name || `Player ${buyerId}`
                };
                const ownerObj = {
                    id: ownerId,
                    name: (window.playersData || []).find(p => String(p.player_id) === String(ownerId))?.name || `Player ${ownerId}`
                };

                if (typeof window.openTradeModal === "function") {
                    window.openTradeModal(buyerObj, ownerObj, Number(tileIndex)); // pass tileIndex
                } else {
                    return { success:false, message:"Trade modal function not found" };
                }

                return { success: true, openModal: true };
            }

            // Offer exists -> now do the real purchase through backend
            url = '../../Backend/GameActions/buyOwnedProperty.php';

            // IMPORTANT: your PHP expects buyer_id, owner_id, property_id, offer
            payload = {
                buyer_id: Number(buyerId),
                owner_id: Number(ownerId),
                property_id: Number(propertyId),
                offer: Number(offerPrice)
            };
        }
        else {
            return { success: false, message: "You already own this property" };
        }

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!data.success) {
            return { success: false, message: data.message || "Transaction failed" };
        }

        // Return updated balance and ownership state
        return {
            success: true,
            newBalance: data.newBalance,
            owned: true, // buyer now owns the property
            message: data.message || "Property purchased successfully"
        };

    } catch (err) {
        console.error("BuyPropertyProxy error:", err);
        return { success: false, message: "Error processing purchase" };
    }
};
