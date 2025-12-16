// CommunityTile.js
import { Tile } from "../Tile.js";

export class CommunityTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "community";
  }
}
