// SellPropertyProxy.js
export const sellPropertyProxy = async (sellerId, propertyId, ownerId = null, buyerId = null, sellPrice = null) => {
    try {
        if (String(ownerId) !== String(sellerId)) {
            return { success: false, message: "You do not own this property" };
        }

        let url = '';
        let payload = { sellerId, propertyId };

        if (!sellPrice || sellPrice <= 0) {
            url = '../../Backend/GameActions/sellPropertyToBank.php';
        } else {
            if (!buyerId) return { success: false, message: "Missing buyerId for player sale" };

            url = '../../Backend/GameActions/sellPropertyToPlayer.php';
            payload = {
                owner_id: sellerId,
                buyer_id: buyerId,
                property_id: propertyId,
                price: sellPrice
            };
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
            owned: false, // property no longer belongs to seller
            message: data.message || "Property sold successfully"
        };

    } catch (err) {
        console.error("SellPropertyProxy error:", err);
        return { success: false, message: "Error processing sale" };
    }
};
