import { spawn } from "node:child_process";

const main = () => {
    const n = 10;
    const minTime = 1; // min sleep time is 1 sec
    const maxTime = 5; // max sleep time is 5 sec

    [...Array(n).keys()].forEach(idx => {
        const job = spawn("php", [
            "./src/main.php", 
            `--value=${idx + 1}`,
            `--sleep-time=${Math.random() * (maxTime - minTime + 1) + minTime}`
        ]);

        // TODO (kharlamov_vs): propper configurable looging
        job.stdout.on("data", (data) => console.log(`${data}`))
        job.stderr.on("data", (data) => console.log(`error (process #${idx + 1}): ${data}`))
    });
}

main();