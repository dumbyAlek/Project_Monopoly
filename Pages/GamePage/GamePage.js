// GamePage,js
console.log("GamePage loaded with mBoard included");

function updatePlayerMoney(playerIndex, amount) {
    const panel = document.querySelector(`.player-panel[data-player-id="${playerIndex}"]`);
    if (!panel) return;

    const moneyEl = panel.querySelector(".money-value");
    if (moneyEl) moneyEl.textContent = amount;
}


function attachPlayerEvents() {
    const panels = document.querySelectorAll(".player-panel");
    panels.forEach(p => {
        p.querySelector(".get-out-jail-btn").onclick = () => GameActionsProxy.getOutOfJail(p);
        p.querySelector(".pay-loan-btn").onclick = () => {
            const amount = parseInt(prompt("Enter loan amount:"));
            if (!isNaN(amount) && amount > 0) GameActionsProxy.payLoan(p, amount);
        }
        // p.querySelector(".pay-debt-btn").onclick = () => GameActionsProxy.payDebt(p);
        // p.querySelector(".ask-debt-btn").onclick = () => GameActionsProxy.askDebt(p);
    });
}

const saveBtn = document.getElementById("save-game-btn");
if (saveBtn) {
    saveBtn.onclick = () => GameFacade.saveGame(currentGameId);
}


// Initial attach
attachPlayerEvents();
GameFacade.enableAutosave(currentGameId);

// ========================================== Buying from Player ==============================================================

let tradeData = {};

function openTradeModal(buyer, owner, tileIndex) {
    tradeData = {
        buyer_id: buyer.id,
        buyer_name: buyer.name,
        owner_id: owner.id,
        owner_name: owner.name,
        tile_index: tileIndex,
        offer: null
    };

    document.getElementById("buyerName").innerText = buyer.name;
    document.getElementById("ownerName").innerText = owner.name;

    document.getElementById("buyerView").style.display = "block";
    document.getElementById("ownerView").style.display = "none";
    document.getElementById("resultView").style.display = "none";

    document.getElementById("tradeModal").style.display = "block";
}

document.getElementById("offerAmount").addEventListener("input", function () {
    const value = parseInt(this.value);
    document.getElementById("proceedBtn").disabled = !(value > 0);
});

document.getElementById("proceedBtn").onclick = () => {
    tradeData.offer = Number(document.getElementById("offerAmount").value);

    document.getElementById("buyerView").style.display = "none";
    document.getElementById("ownerView").style.display = "block";
    document.getElementById("offeredPrice").innerText = tradeData.offer;
};

document.getElementById("declineTradeBtn").onclick = () => {
    document.getElementById("ownerView").style.display = "none";
    document.getElementById("resultView").style.display = "block";
    document.getElementById("resultMessage").innerText =
        "❌ The owner declined your offer.";
};

document.getElementById("acceptTradeBtn").onclick = async () => {
    const offer = Number(tradeData.offer);
    if (!offer || offer <= 0) return alert("Invalid offer");

    const buyerPanel = document.querySelector(`.player-panel[data-player-id="${tradeData.buyer_id}"]`);
    if (!buyerPanel) return alert("Buyer panel not found");

    // This goes through: GameActionsProxy -> checkPropertyStatus -> BuyPropertyProxy -> buyOwnedProperty.php
    const result = await window.GameActionsProxy.buyProperty(buyerPanel, tradeData.tile_index, offer);

    document.getElementById("ownerView").style.display = "none";
    document.getElementById("resultView").style.display = "block";

    if (result?.success) {
        document.getElementById("resultMessage").innerText = "🤝 Deal Done! Property transferred successfully.";
        refreshSidebars();
    } else {
        document.getElementById("resultMessage").innerText = "❌ " + (result?.message || "Transaction failed");
    }
};


function closeTradeModal() {
    document.getElementById("tradeModal").style.display = "none";
}

// ========================================== Selling to Player ==============================================================

let sellTradeData = {};

function openSellTradeModal(owner, buyer, propertyId) {
    sellTradeData = {
        owner_id: owner.id,
        owner_name: owner.name,
        buyer_id: buyer.id,
        buyer_name: buyer.name,
        property_id: propertyId,
        price: null
    };

    document.getElementById("sellerName").innerText = owner.name;
    document.getElementById("buyerConfirmName").innerText = buyer.name;

    document.getElementById("sellerView").style.display = "block";
    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "none";

    document.getElementById("sellTradeModal").style.display = "block";
}

document.getElementById("askingPrice").addEventListener("input", function () {
    document.getElementById("proceedToBuyerBtn").disabled = !(this.value > 0);
});

document.getElementById("proceedToBuyerBtn").onclick = () => {
    sellTradeData.price = document.getElementById("askingPrice").value;

    document.getElementById("finalPrice").innerText = sellTradeData.price;
    document.getElementById("sellerView").style.display = "none";
    document.getElementById("buyerConfirmView").style.display = "block";
};

document.getElementById("declineSellBtn").onclick = () => {
    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "block";
    document.getElementById("sellResultMessage").innerText =
        "❌ Buyer declined the offer.";
};

document.getElementById("acceptSellBtn").onclick = () => {
    fetch("../../Backend/GameActions/sellPropertyToPlayer.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(sellTradeData)
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("buyerConfirmView").style.display = "none";
        document.getElementById("sellResultView").style.display = "block";

        if (data.success) {
            document.getElementById("sellResultMessage").innerText =
                "🤝 Deal completed successfully.";
            refreshSidebars();
        } else {
            document.getElementById("sellResultMessage").innerText =
                "❌ " + data.message;
        }
    });
};

function closeSellTradeModal() {
    document.getElementById("sellTradeModal").style.display = "none";
}

function refreshSidebars() {
    fetch(`../../Backend/getSidebarData.php?game_id=${currentGameId}`)
        .then(res => res.json())
        .then(data => {
            // Update bank
            document.querySelector(".bank-money").innerText =
                `Bank Money: $${data.bank.total_funds}`;

            // Update players
            data.players.forEach(p => {
                const panel = document.querySelector(
                    `.player-panel[data-player-id="${p.player_id}"]`
                );
                if (!panel) return;

                panel.querySelector(".money-value").innerText = p.money;
                panel.querySelector("p:nth-child(2)").innerHTML =
                    `Properties: ${p.number_of_properties} ($${p.propertyWorthCash})`;
                panel.querySelector("p:nth-child(4)").innerHTML =
                    `Loan: $0 <button class="pay-loan-btn">Pay</button>`;
                panel.querySelector("p:nth-child(5)").innerText =
                    `Debt from Players: $${p.debt_from_players}`;
            });

            // Re-attach button events
            attachPlayerEvents();
        });
}

function buildSavePayload() {
  // Bank money (from UI you already update)
  const bankText = document.querySelector(".bank-money")?.innerText || "";
  const bankMoney = Number((bankText.match(/\$([0-9]+)/)?.[1]) ?? 0);

  // Players from panels (source of truth for money/wallet UI)
  const playerPanels = document.querySelectorAll(".player-panel");
  const players = Array.from(playerPanels).map(panel => {
    const playerId = Number(panel.dataset.playerId);

    const money = Number(panel.querySelector(".money-value")?.innerText ?? 0);

    // Properties: "Properties: X ($Y)"
    const propsLine = panel.querySelector("p:nth-child(2)")?.innerText || "";
    const propsCount = Number((propsLine.match(/Properties:\s*(\d+)/)?.[1]) ?? 0);
    const propsWorth = Number((propsLine.match(/\(\$(\d+)\)/)?.[1]) ?? 0);

    const debtFromLine = panel.querySelector("p:nth-child(5)")?.innerText || "";
    const debtFrom = Number((debtFromLine.match(/\$([0-9]+)/)?.[1]) ?? 0);

    // If you track these in UI later, wire them in; for now keep 0
    const debtTo = 0;

    // Position: use window.mBoard players if available, else fallback 0
    const pos =
      (window.__mPlayers?.find(x => x.id === playerId)?.pos) ??
      (window.playersData?.find(x => x.player_id === playerId)?.position) ??
      0;

    // If you render these somewhere, wire them; else fallback
    const inJail =
      (window.playersData?.find(x => x.player_id === playerId)?.is_in_jail) ? 1 : 0;

    const hasCard =
      (window.playersData?.find(x => x.player_id === playerId)?.has_get_out_card) ? 1 : 0;

    return {
      playerId,
      money,
      position: Number(pos),
      inJail: !!inJail,
      hasGetOutCard: !!hasCard,
      propertiesCount: propsCount,
      propertiesWorth: propsWorth,
      debtToPlayers: debtTo,
      debtFromPlayers: debtFrom
    };
  });

  // Properties: use tiles array if it contains DB property_id + owner_id etc.
  const properties = (window.tiles || []).map((t, tileIndex) => ({
    tileIndex,
    property_id: Number(t.id ?? 0),
    owner_id: t.owner_id ?? null,
    house_count: Number(t.house_count ?? 0),
    hotel_count: Number(t.hotel_count ?? 0),
    is_mortgaged: !!t.is_mortgaged
  })).filter(p => p.property_id > 0);

  return {
    gameId: Number(window.currentGameId),
    bank: { totalFunds: bankMoney },
    players,
    properties
  };
}

// ✅ Save when user reloads/closes
window.addEventListener("pagehide", () => {
  try {
    const payload = buildSavePayload();
    const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
    navigator.sendBeacon("../../Backend/saveGame.php", blob);
  } catch (e) {
    console.error("Autosave (pagehide) failed:", e);
  }
});

