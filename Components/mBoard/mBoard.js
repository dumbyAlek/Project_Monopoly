// mBoard.js
import { animateDice, diceFaces, getRandomPosition, mappingLabels , generateTiles} from './mBoardVisuals.js';

const board = document.querySelector(".board");
const rollBtn = document.getElementById("rollBtn");
const diceResult = document.getElementById("diceResult");

generateTiles();

const players = [
  { id: 1, pos: 0, element: null },
  { id: 2, pos: 0, element: null },
  { id: 3, pos: 0, element: null },
  { id: 4, pos: 0, element: null }
];

let currentPlayerIndex = 0;

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
  try {
    const res = await fetch("../../Backend/mBoard/rollDice.php");
    const data = await res.json();
    const die1 = data.die1;
    const die2 = data.die2;
    const total = die1 + die2;

    animateDice(document.getElementById("dice1"), diceFaces[die1-1]);
    animateDice(document.getElementById("dice2"), diceFaces[die2-1]);

    const p = players[currentPlayerIndex];
    p.pos = (p.pos + total) % 40;
    movePlayer(p, mappingLabels);

    diceResult.textContent = `Player ${p.id} rolled ${die1} + ${die2} = ${total}`;
    currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
  } catch (err) { console.error("Dice error:", err); }
}

// Event listener
initPlayers(mappingLabels);
rollBtn.addEventListener("click", () => rollDice(mappingLabels));
