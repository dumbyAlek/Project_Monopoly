// UtilityTile.js
import { Tile } from "../Tile.js";

export class UtilityTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "utility";
  }
}
