// mBoard.js
import { animateDice, diceFaces, getRandomPosition, mappingLabels , generateTiles} from './mBoardVisuals.js';
import { tiles } from './mBoardVisuals.js';
import { TileDecorator } from './TileDecorator.js';
import { GameActionsProxy } from '../../Pages/GamePage/GameActionsProxy.js';

const rollBtn = document.getElementById("rollBtn");
const endTurnBtn = document.getElementById("endTurnBtn");

const gameProperties = window.gameProperties || {};

function setTurnPhase(phase) {
  if (phase === "roll") {
    rollBtn.classList.remove("hidden", "transparent");
    endTurnBtn.classList.add("hidden");
  }

  if (phase === "end") {
    rollBtn.classList.add("transparent");
    endTurnBtn.classList.remove("hidden");
  }
}

window.mappingLabels = mappingLabels;

const diceResult = document.getElementById("diceResult");

generateTiles();
setTurnPhase("roll");

const players = [
  { id: 1, pos: 0, element: null },
  { id: 2, pos: 0, element: null },
  { id: 3, pos: 0, element: null },
  { id: 4, pos: 0, element: null }
];

let currentPlayerIndex = 0;
let turnLocked = false;  // prevents advancing turn until actions are done


// Move a player to its tile
export function movePlayer(playerObj, mappingLabels) {
  const tile = document.getElementById(mappingLabels[playerObj.pos]);
  if (!tile) return;
  const placeholder = tile.querySelector(".player-placeholder");
  if (!placeholder.contains(playerObj.element)) {
    placeholder.appendChild(playerObj.element);
  }
}

// Initialize player pieces
export function initPlayers(mappingLabels) {
  players.forEach(p => {
    const piece = document.createElement("div");
    piece.classList.add("player", `p${p.id}`);
    piece.textContent = p.id;
    p.element = piece;
    movePlayer(p, mappingLabels);
  });
}

// Roll dice and update player position
export async function rollDice(mappingLabels) {
  if (turnLocked) return; // block rolling if turn is in progress

  try {
    const res = await fetch("../../Backend/mBoard/rollDice.php");
    const data = await res.json();
    const die1 = data.die1;
    const die2 = data.die2;
    const total = die1 + die2;

    const p = players[currentPlayerIndex];

    animateDice(document.getElementById("dice1"), diceFaces[die1-1]);
    animateDice(document.getElementById("dice2"), diceFaces[die2-1]);

    p.pos = (p.pos + total) % 40;

    if (tiles[p.pos].category === "goToJail") {
      const jailIndex = tiles.findIndex(t => t.category === "jail");
      if (jailIndex !== -1) p.pos = jailIndex;
    }

    movePlayer(p, mappingLabels);
    enableTileActions(p.pos);

    diceResult.textContent = `Player ${p.id} rolled ${die1} + ${die2} = ${total}`;

    // Lock the turn until player finishes actions
    turnLocked = true;
    setTurnPhase("end");


  } catch (err) { console.error("Dice error:", err); }
}

if (rollBtn) {
  rollBtn.addEventListener("click", () => rollDice(mappingLabels));
}
initPlayers(mappingLabels);


// Add button event listeners for all tiles
tiles.forEach((t, i) => {
    const tileEl = document.getElementById(mappingLabels[i]);
    const buyBtn = tileEl.querySelector(".buy-btn");
    const sellBtn = tileEl.querySelector(".sell-btn");

    if (buyBtn) buyBtn.addEventListener("click", () => {
      const player = players[currentPlayerIndex];
      const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);

      GameActionsProxy.buyProperty(playerPanel, i);

      turnLocked = false;
      currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
      enableTileActions(-1);
      setTurnPhase("roll");
    });

    if (sellBtn) sellBtn.addEventListener("click", () => {
      const player = players[currentPlayerIndex];
      const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);

      GameActionsProxy.sellProperty(playerPanel, i);

      turnLocked = false;
      currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
      enableTileActions(-1);
      setTurnPhase("roll");
    });


});

export function updateHouses(tileIndex, houseCount, hasHotel = false) {
  const tile = document.getElementById(mappingLabels[tileIndex]);
  if (!tile) return;

  const container = tile.querySelector(".houses-container");
  container.innerHTML = ""; // clear previous houses/hotel

  // Use TileDecorator's static methods 
  if (hasHotel) {
    TileDecorator.addHotel(tile);
  } else if (houseCount > 0) {
    TileDecorator.addHouses(tile, houseCount);
  }
}

function enableTileActions(tileIndex) {
  tiles.forEach((_, i) => {
    const tileEl = document.getElementById(mappingLabels[i]);
    if (!tileEl) return;

    const buyBtn = tileEl.querySelector(".buy-btn");
    const sellBtn = tileEl.querySelector(".sell-btn");

    if (buyBtn) buyBtn.disabled = i !== tileIndex;
    if (sellBtn) sellBtn.disabled = i !== tileIndex;
  });
}

if (endTurnBtn) {
  endTurnBtn.addEventListener("click", () => {
    if (!turnLocked) return;

    turnLocked = false;
    currentPlayerIndex = (currentPlayerIndex + 1) % players.length;

    enableTileActions(-1);
    setTurnPhase("roll");
  });
}

for (let i = 0; i < tiles.length; i++) {
    if (gameProperties[i]) {
        tiles[i].property = gameProperties[i];
    }
}