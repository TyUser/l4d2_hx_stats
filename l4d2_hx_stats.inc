#if defined _l4d2_hx_stats_included
    #endinput
#endif
#define _l4d2_hx_stats_included

/**
 * Get the player's points
 *
 * @param client        Client index. From 1 to MaxClients
 * @return              Number of points
 */
native int HxGetClientPoints(int client);

/**
 * Get the average skill of the team
 *
 * @return              Value from 1 to 10
 */
native int HxGetAverageSkill();
