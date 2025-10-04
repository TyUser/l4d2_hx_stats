# [L4D2] hx_stats
Сайт: [forums.alliedmods.net](https://forums.alliedmods.net/showthread.php?t=298535)
===========

Основные функции плагина (ru):
* Предназначен для L4D2 coop
* Подсчитывает количество убитых Boomer, Charger, Hunter, Infected, Jockey, Smoker, Spitter, Tank, Witch
* Подсчитывает общее время проведенное в игре
* Фиксирует последнее время посещения
* Подсчитывает Points игроков
* Изменение цвета игрока в зависимости от количества Points
* Блокирует команду go_away_from_keyboard тем кто имеет меньше 50 Points
* Блокирует команду callvote тем кто имеет меньше 500 Points
* Команда !rank (в консоли sm_rank) Отображение статистики игрока
* Команда !top (в консоли sm_top) список top15 игроков

Конвертация старой версии базы данных l4d2_hx_stats_1.2 на l4d2_hx_stats_1.4 (ru):
* ALTER TABLE `l4d2_stats` ENGINE = InnoDB, CONVERT TO CHARACTER SET utf8mb4;

Main plugin functions (en):
* Designed for L4D2 Co-op
* Tracks the number of killed Common Infected, Boomers, Chargers, Hunters, Jockeys, Smokers, Spitters, Tanks, and Witches.
* Tracks total playtime
* Records last visit time
* Counts players' Points
* Changes a player's color based on their Points
* Blocks the go_away_from_keyboard command for players with fewer than 50 Points
* Blocks the callvote command for players with fewer than 500 Points
* !rank command (or sm_rank in console): Displays the player's statistics
* !top command (or sm_top in console): Shows the list of the top 15 players

Converting the old version of the l4d2_hx_stats_1.2 database to l4d2_hx_stats_1.4 (en):
* ALTER TABLE `l4d2_stats` ENGINE = InnoDB, CONVERT TO CHARACTER SET utf8mb4;