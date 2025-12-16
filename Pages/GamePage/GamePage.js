// GamePage,js
console.log("GamePage loaded with mBoard included");

function updatePlayerMoney(playerIndex, amount) {
    const panel = document.querySelector(`.player-panel[data-player-id="${playerIndex}"]`);
    if (!panel) return;

    const moneyEl = panel.querySelector(".money-value");
    if (moneyEl) moneyEl.textContent = amount;
}
window.updatePlayerMoney = updatePlayerMoney;

window.openSellTradeModal = openSellTradeModal;
window.closeSellTradeModal = closeSellTradeModal;


function attachPlayerEvents() {
  const panels = document.querySelectorAll(".player-panel");
  panels.forEach(p => {
    const jailBtn = p.querySelector(".get-out-jail-btn");
    if (jailBtn) jailBtn.onclick = () => GameActionsProxy.getOutOfJail(p);

    const payLoanBtn = p.querySelector(".pay-loan-btn");
    if (payLoanBtn) {
      payLoanBtn.onclick = () => {
        const amount = parseInt(prompt("Enter loan amount:"));
        if (!isNaN(amount) && amount > 0) GameActionsProxy.payLoan(p, amount);
      };
    }
  });
}


const saveBtn = document.getElementById("save-game-btn");
if (saveBtn) {
    saveBtn.onclick = () => GameFacade.saveGame(currentGameId);
}


// Initial attach
window.addEventListener("DOMContentLoaded", () => {
  attachPlayerEvents();
  GameFacade.enableAutosave(currentGameId);
});

// ========================================== Buying from Player ==============================================================

window.tradeData = window.tradeData || {};

function openTradeModal(buyer, owner, tileIndex) {
    window.tradeData = {
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
    window.tradeData.offer = Number(document.getElementById("offerAmount").value);

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
    const offer = Number(window.tradeData.offer);
    if (!offer || offer <= 0) return alert("Invalid offer");

    const buyerPanel = document.querySelector(`.player-panel[data-player-id="${window.tradeData.buyer_id}"]`);
    if (!buyerPanel) return alert("Buyer panel not found");

    // This goes through: GameActionsProxy -> checkPropertyStatus -> BuyPropertyProxy -> buyOwnedProperty.php
    const result = await window.GameActionsProxy.buyProperty(buyerPanel, window.tradeData.tile_index, offer);

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
window.sellTradeData = window.sellTradeData || {};

function openSellTradeModal(owner, propertyId, propertyName = "", tileIndex = null) {

  function mustGet(id) {
    const el = document.getElementById(id);
    if (!el) {
      console.error("Missing element:", id);
      throw new Error("Missing element: " + id);
    }
    return el;
  }

  window.sellTradeData = {
    owner_id: owner.id,
    owner_name: owner.name,
    buyer_id: null,
    buyer_name: null,
    property_id: propertyId,
    price: null,
    tile_index: tileIndex
  };

  window.sellTradeData.tile_index = tileIndex;

  // reset views
    const sellOptionsView = mustGet("sellOptionsView");
    const sellToPlayerFormView = mustGet("sellToPlayerFormView");
    const buyerConfirmView = mustGet("buyerConfirmView");
    const sellResultView = mustGet("sellResultView");

    sellOptionsView.style.display = "block";
    sellToPlayerFormView.style.display = "none";
    buyerConfirmView.style.display = "none";
    sellResultView.style.display = "none";


  document.getElementById("sellerName").innerText = owner.name;
  document.getElementById("sellPropertyLabel").innerText =
    propertyName ? `Property: ${propertyName}` : "";

  // reset inputs
  document.getElementById("askingPrice").value = "";
  document.getElementById("sellBuyerSelect").innerHTML =
    `<option value="">-- Select buyer --</option>`;

  // populate dropdown from window.playersData (exclude owner)
  (window.playersData || [])
    .filter(p => Number(p.player_id) !== Number(owner.id))
    .forEach(p => {
      const opt = document.createElement("option");
      opt.value = p.player_id;
      opt.textContent = p.name;
      document.getElementById("sellBuyerSelect").appendChild(opt);
    });

  document.getElementById("proceedToBuyerBtn").disabled = true;

  document.getElementById("sellTradeModal").style.display = "block";
}


  window.addEventListener("DOMContentLoaded", () => {

  document.getElementById("sellToPlayerBtn").onclick = () => {
    document.getElementById("sellOptionsView").style.display = "none";
    document.getElementById("sellToPlayerFormView").style.display = "block";
  };

  document.getElementById("backToSellOptionsBtn").onclick = () => {
    document.getElementById("sellToPlayerFormView").style.display = "none";
    document.getElementById("sellOptionsView").style.display = "block";
  };



  function canProceedSellToPlayer() {
    const price = Number(document.getElementById("askingPrice").value);
    const buyerId = document.getElementById("sellBuyerSelect").value;
    return price > 0 && buyerId;
  }

  document.getElementById("askingPrice").addEventListener("input", () => {
    document.getElementById("proceedToBuyerBtn").disabled = !canProceedSellToPlayer();
  });

  document.getElementById("sellBuyerSelect").addEventListener("change", () => {
    document.getElementById("proceedToBuyerBtn").disabled = !canProceedSellToPlayer();
  });


  document.getElementById("proceedToBuyerBtn").onclick = () => {
    const price = Number(document.getElementById("askingPrice").value);
    const buyerId = Number(document.getElementById("sellBuyerSelect").value);

    const buyerObj = (window.playersData || []).find(p => Number(p.player_id) === buyerId);
    if (!buyerObj) return alert("Invalid buyer selected");

    window.sellTradeData.price = price;
    window.sellTradeData.buyer_id = buyerId;
    sellTradeData.buyer_name = buyerObj.name;

    document.getElementById("finalPrice").innerText = price;
    document.getElementById("buyerConfirmName").innerText = buyerObj.name;

    document.getElementById("sellToPlayerFormView").style.display = "none";
    document.getElementById("buyerConfirmView").style.display = "block";
  };


  document.getElementById("declineSellBtn").onclick = () => {
      document.getElementById("buyerConfirmView").style.display = "none";
      document.getElementById("sellResultView").style.display = "block";
      document.getElementById("sellResultMessage").innerText =
          "❌ Buyer declined the offer.";
  };

  document.getElementById("acceptSellBtn").onclick = async () => {
    const ownerPanel = document.querySelector(`.player-panel[data-player-id="${window.sellTradeData.owner_id}"]`);
    if (!ownerPanel) return alert("Owner panel not found");

    const res = await window.GameActionsProxy.confirmSellProperty(
      ownerPanel,
      window.sellTradeData.tile_index,
      "player",
      window.sellTradeData.buyer_id,
      window.sellTradeData.price
    );

      if (res.success && window.sellTradeData.tile_index != null) {
        window.tiles[window.sellTradeData.tile_index].owner_id = window.sellTradeData.buyer_id;
      }

    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "block";

    document.getElementById("sellResultMessage").innerText =
      res.success ? "🤝 Deal completed successfully." : ("❌ " + res.message);

    if (res.success) refreshSidebars();
  };





  document.getElementById("sellToBankBtn").onclick = async () => {
    const ownerPanel = document.querySelector(`.player-panel[data-player-id="${window.sellTradeData.owner_id}"]`);
    if (!ownerPanel) return alert("Owner panel not found");

    const res = await window.GameActionsProxy.confirmSellProperty(ownerPanel, window.sellTradeData.tile_index, "bank");

    if (res.success && window.sellTradeData.tile_index != null) {
      window.tiles[window.sellTradeData.tile_index].owner_id = null;
    }

    document.getElementById("sellOptionsView").style.display = "none";
    document.getElementById("sellResultView").style.display = "block";
    document.getElementById("sellResultMessage").innerText = res.success ? "✅ Sold to bank." : ("❌ " + res.message);

    if (res.success) refreshSidebars();
  };

  });

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

            const moneyEl = panel.querySelector(".money-value");
            if (moneyEl) moneyEl.innerText = p.money;

            // Prefer class hooks if you have them. If not, use data-role hooks (recommended below).
            const propsEl = panel.querySelector('[data-role="props"]');
            if (propsEl) propsEl.innerText = `Properties: ${p.number_of_properties} ($${p.propertyWorthCash})`;

            const loanEl = panel.querySelector('[data-role="loan"]');
            if (loanEl) loanEl.innerHTML = `Loan: $0 <button class="pay-loan-btn">Pay</button>`;

            const debtEl = panel.querySelector('[data-role="debt"]');
            if (debtEl) debtEl.innerText = `Debt from Players: $${p.debt_from_players}`;

            });

            // Re-attach button events
            attachPlayerEvents();
        });
}

  function closeSellTradeModal() {
    document.getElementById("sellTradeModal").style.display = "none";

    // reset views for next time
    document.getElementById("sellOptionsView").style.display = "block";
    document.getElementById("sellToPlayerFormView").style.display = "none";
    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "none";

    // clear text
    document.getElementById("sellResultMessage").innerText = "";
    document.getElementById("sellPropertyLabel").innerText = "";
  }

window.openTradeModal = openTradeModal;