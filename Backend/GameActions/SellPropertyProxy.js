export const sellPropertyProxy = async (
  sellerId,
  propertyId,
  ownerId = null,
  mode = "bank",      // "bank" | "player"
  buyerId = null,
  sellPrice = null
) => {

    try {
        const gameId = Number(window.currentGameId ?? 0);
        if (!gameId) return { success: false, message: "Missing gameId" };

        if (String(ownerId) !== String(sellerId)) {
            return { success: false, message: "You do not own this property" };
        }
        let url = '';
        let payload = null;

        if (mode === "bank") {
        url = '../../Backend/GameActions/sellPropertyToBank.php';
        payload = { sellerId, propertyId, gameId: Number(window.currentGameId ?? window.currentGameId ?? currentGameId ?? 0) };
        } else if (mode === "player") {
        if (!buyerId) return { success: false, message: "Missing buyerId for player sale" };
        if (String(buyerId) === String(sellerId)) {
            return { success: false, message: "Buyer cannot be the seller" };
        }
        if (!sellPrice || Number(sellPrice) <= 0) return { success: false, message: "Invalid asking price" };

        url = '../../Backend/GameActions/sellPropertyToPlayer.php';
        payload = {
        owner_id: Number(sellerId),
        buyer_id: Number(buyerId),
        property_id: Number(propertyId),
        price: Number(sellPrice),
        gameId: Number(window.currentGameId ?? window.currentGameId ?? currentGameId ?? 0)
        };
        } else {
        return { success: false, message: "Invalid sell mode" };
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
        message: data.message || "Property sold successfully",
        buyerNewBalance: data.buyerNewBalance ?? (mode === "player" ? data.newBalance : null),
        sellerNewBalance: data.sellerNewBalance ?? data.newBalance,
        newOwnerId: data.newOwnerId ?? null,
        owned: false
        };

    } catch (err) {
        console.error("SellPropertyProxy error:", err);
        return { success: false, message: "Error processing sale" };
    }
};
