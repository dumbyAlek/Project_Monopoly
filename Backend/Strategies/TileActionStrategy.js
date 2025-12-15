export class TileActionStrategy {
  /**
   * @param {object} ctx
   * @param {number} ctx.gameId
   * @param {number} ctx.playerId
   * @param {object} ctx.tile  // tile object from TileFactory
   * @param {object} ctx.db    // your db adapter/connection
   * @param {object} ctx.services // money/log helpers
   */
  async execute(ctx) {
    throw new Error("TileActionStrategy.execute() not implemented");
  }
}
