// SellPropertyProxy.js
export const sellPropertyProxy = async (sellerId, propertyId, ownerId = null, sellPrice = null) => {
    try {
        if (ownerId !== sellerId) {
            return { success: false, message: "You do not own this property" };
        }

        let url = '';
        let payload = { sellerId, propertyId };

        if (!sellPrice || sellPrice <= 0) {
            // Default: sell to bank at standard price
            url = '../../Backend/GameActions/sellPropertyToBank.php';
        } else {
            // Selling to another player (bargain/negotiation)
            url = '../../Backend/GameActions/sellPropertyToPlayer.php';
            payload.offerPrice = sellPrice;
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
