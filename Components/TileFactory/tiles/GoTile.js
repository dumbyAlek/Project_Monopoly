// GoTile.js
import { Tile } from "../Tile.js";

export class GoTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "go";
  }
}
