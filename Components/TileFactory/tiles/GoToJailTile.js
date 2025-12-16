// GoToJailTile.js
import { Tile } from "../Tile.js";

export class GoToJailTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "goToJail";
    this.isGoToJail = true;
  }
}
