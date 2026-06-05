import mysql from 'mysql2/promise';
import { faker } from '@faker-js/faker';
import * as bcrypt from 'bcrypt';
import chalk from 'chalk';
import * as dotenv from 'dotenv';
import * as path from 'path';

dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Parse command line arguments
function parseArgs(): { count: number; password: string } {
  const args = process.argv.slice(2);
  let count = 10;
  let password = 'password';

  for (const arg of args) {
    if (arg.startsWith('--count=')) {
      count = parseInt(arg.split('=')[1], 10);
    } else if (arg.startsWith('--password=')) {
      password = arg.split('=')[1];
    }
  }

  if (isNaN(count) || count < 1) {
    console.error(chalk.red('Error: --count must be a positive number'));
    process.exit(1);
  }

  return { count, password };
}

// Random Khmer names
const khmerFirstNames = [
  'Sok', 'Dara', 'Bopha', 'Vicheka', 'Sreypich', 'Chanthy',
  'Ratanak', 'Kunthea', 'Maly', 'Pisey', 'Vuthy', 'Nary',
  'Sophal', 'Chanda', 'Raksmey', 'Leakhena', 'Koly', 'Sambath',
  'Makara', 'Thida', 'Pheap', 'Bunthan', 'Channary', 'Heng'
];

const khmerLastNames = [
  'Sok', 'Chan', 'Chea', 'Hong', 'Keo', 'Ly',
  'Meas', 'Nop', 'Ouk', 'Pen', 'Sam', 'Sao',
  'Touch', 'Uk', 'Vann', 'Yim', 'Kong', 'Mao',
  'Nhem', 'Pich', 'Ros', 'Seng', 'Thon', 'Yin'
];

const khmerLocations = [
  'ភ្នំពេញ', 'សៀមរាប', 'បាត់ដំបង', 'កំពង់ចាម',
  'កំពត', 'ក្រចេះ', 'ស្ទឹងត្រែង', 'ព្រះសីហនុ',
  'មណ្ឌលគិរី', 'រតនគិរី', 'កំពង់ធំ', 'ពោធិ៍សាត់'
];

const khmerBios = [
  'អ្នកលក់ដ៏ទុកចិត្តបាន',
  'លក់ផលិតផលគុណភាព',
  'Trusted seller in Cambodia',
  'Quality products, fair prices',
  'Professional seller',
  'Best deals guaranteed'
];

async function main() {
  const { count, password } = parseArgs();

  console.log(chalk.bold.green('\nStarting user seeder...'));
  console.log(chalk.bold('Users to create:'), chalk.cyan(count));
  console.log(chalk.bold('Default password:'), chalk.yellow(password) + '\n');

  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'mysql_db',
    user: process.env.DB_USER || 'app_user',
    password: process.env.DB_PASSWORD || 'secret',
    database: process.env.DB_NAME || 'app_db',
    port: parseInt(process.env.DB_PORT || '3306'),
  });

  try {
    const hashedPassword = await bcrypt.hash(password, 10);

    // Get existing emails to avoid duplicates
    const [existingUsers] = await connection.query('SELECT email FROM User');
    const existingEmails = new Set(
      (existingUsers as Array<{ email: string }>)
        .map(u => u.email)
        .filter(Boolean)
        .map(e => e.toLowerCase())
    );

    console.log(chalk.bold('Found'), chalk.green(existingEmails.size), chalk.bold('existing users') + '\n');
    console.log(chalk.bold.blue('Creating users...\n'));

    let created = 0;
    let skipped = 0;

    for (let i = 1; i <= count; i++) {
      const firstName = faker.helpers.arrayElement(khmerFirstNames);
      const lastName = faker.helpers.arrayElement(khmerLastNames);
      const name = `${firstName} ${lastName}`;
      const email = faker.internet.email({ firstName: firstName, lastName: lastName }).toLowerCase();

      if (existingEmails.has(email)) {
        console.log(chalk.dim(`  [skip] ${email}`));
        skipped++;
        continue;
      }

      const location = faker.helpers.arrayElement(khmerLocations);
      const bio = faker.helpers.arrayElement(khmerBios);
      const phone1 = `0${faker.number.int({ min: 10000000, max: 99999999 })}`;
      const hasPhone2 = faker.datatype.boolean({ probability: 0.3 });
      const phone2 = hasPhone2 ? `0${faker.number.int({ min: 10000000, max: 99999999 })}` : null;

      // Insert user
      const [userResult] = await connection.query(
        'INSERT INTO `User` (name, first_name, last_name, email, password, avatar, is_admin, can_post) VALUES (?, ?, ?, ?, ?, 0, 0, 1)',
        [name, firstName, lastName, email, hashedPassword]
      );
      const userId = (userResult as any).insertId;

      // Insert profile
      await connection.query(
        'INSERT INTO user_profile (user_id, phone1, phone2, bio) VALUES (?, ?, ?, ?)',
        [userId, phone1, phone2, bio]
      );

      existingEmails.add(email);
      console.log(chalk.dim(`  [${created + 1}]`), chalk.bold(name), chalk.dim(email), chalk.green(phone1));
      created++;
    }

    console.log(chalk.bold.green('\nUser seeding complete'));
    console.log(chalk.bold('Created:'), chalk.green(created));
    console.log(chalk.bold('Skipped:'), chalk.yellow(skipped));
    console.log(chalk.bold('Password:'), chalk.cyan(password) + '\n');

  } catch (error) {
    console.error(chalk.red('Error during user seeding:'), error);
    process.exit(1);
  } finally {
    await connection.end();
  }
}

main();
