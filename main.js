import { spawn } from "node:child_process";

const main = () => {
    const n = 10;

    [...Array(n).keys()].forEach(idx => {
        const job = spawn("php", ["./src/main.php", `--value=${idx + 1}`]);

        // TODO (kharlamov_vs): propper configurable looging
        job.stdout.on("data", (data) => console.log(`${data}`))
        job.stderr.on("data", (data) => console.log(`error (process #${idx + 1}): ${data}`))
    });
}

main();