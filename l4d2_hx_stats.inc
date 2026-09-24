#if defined _l4d2_hx_stats_included
    #endinput
#endif
#define _l4d2_hx_stats_included

/**
 * Retrieves the total points of a specific player.
 *
 * @note Returns 0 for fake clients (bots) or if the client is not fully loaded.
 *
 * @param client        Client index (1 to MaxClients).
 * @return              The number of points, or 0 if the client is invalid or a bot.
*/
native int HxGetClientPoints(int client);

/**
 * Retrieves the calculated average skill level of the current team.
 *
 * @note The value is updated dynamically during the round based on players' points.
 *
 * @return              An integer value representing average skill ranging from 1 to 10.
*/
native int HxGetAverageSkill();
