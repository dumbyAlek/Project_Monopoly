// OtherTile.js
import { Tile } from "../Tile.js";

export class OtherTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "other";
  }
}
