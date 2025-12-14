// BuyPropertyProxy.js
export const buyPropertyProxy = async (buyerId, propertyId, ownerId = null, offerPrice = null) => {
    try {
        let url = '';
        let payload = {
            buyer_id: buyerId,
            property_id: propertyId
        };


        if (ownerId === null) {
            // Property is unowned → buy from bank
            url = 'buyUnownedProperty.php';
            if (offerPrice) payload.offerPrice = offerPrice;
        } else if (ownerId !== buyerId) {
            // Property owned by another player → buy from player
            url = 'buyOwnedProperty.php';
            payload.sellerId = ownerId;
            if (offerPrice) payload.offerPrice = offerPrice;
        } else {
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
