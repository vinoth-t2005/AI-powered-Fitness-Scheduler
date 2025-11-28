import java.util.*;

public class Main {
    static int R, C;
    static char[][] grid;
    static String seq;

    // Directions: up, right, down, left
    static int[] dr = {-1, 0, 1, 0};
    static int[] dc = {0, 1, 0, -1};

    public static void main(String[] args) {
        Scanner sc = new Scanner(System.in);

        R = sc.nextInt();
        C = sc.nextInt();
        sc.nextLine(); // consume newline

        grid = new char[R][C];
        for (int i = 0; i < R; i++) {
            grid[i] = sc.nextLine().trim().toCharArray();
        }

        seq = sc.nextLine().trim();

        Set<String> possiblePositions = new HashSet<>();

        // Try every open cell as starting point
        for (int r = 0; r < R; r++) {
            for (int c = 0; c < C; c++) {
                if (grid[r][c] == '#') continue;

                // Try all 4 starting directions
                for (int dir = 0; dir < 4; dir++) {
                    int x = r, y = c;
                    int d = dir;
                    boolean valid = true;

                    for (char move : seq.toCharArray()) {
                        if (move == 'S') {
                            x += dr[d];
                            y += dc[d];
                            // Check bounds and obstacle
                            if (x < 0 || y < 0 || x >= R || y >= C || grid[x][y] == '#') {
                                valid = false;
                                break;
                            }
                        } else if (move == 'L') {
                            d = (d + 3) % 4; // left turn
                        } else if (move == 'R') {
                            d = (d + 1) % 4; // right turn
                        }
                    }

                    if (valid) {
                        possiblePositions.add(x + "," + y);
                    }
                }
            }
        }

        if (possiblePositions.isEmpty()) {
            System.out.println("Impossible");
        } else {
            System.out.println(possiblePositions.size());
        }
    }
}
