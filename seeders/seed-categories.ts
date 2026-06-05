import mysql from 'mysql2/promise';
import chalk from 'chalk';
import * as dotenv from 'dotenv';
import * as path from 'path';

dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Parse command line arguments
function parseArgs(): { count: number } {
  const args = process.argv.slice(2);
  let count = 0; // 0 means all

  for (const arg of args) {
    if (arg.startsWith('--count=')) {
      count = parseInt(arg.split('=')[1], 10);
    }
  }

  if (count < 0) {
    console.error(chalk.red('Error: --count must be a positive number'));
    process.exit(1);
  }

  return { count };
}

// Khmer marketplace categories
const categories = [
  { name: 'គ្រឿងអេឡិចត្រូនិច', nameEn: 'Electronics' },
  { name: 'រថយន្ត', nameEn: 'Cars' },
  { name: 'ម៉ូតូ', nameEn: 'Motorcycles' },
  { name: 'អចលនទ្រព្យ', nameEn: 'Real Estate' },
  { name: 'ផ្ទះជួល', nameEn: 'House for Rent' },
  { name: 'ដីធ្លី', nameEn: 'Land' },
  { name: 'ទូរស័ព្ទ', nameEn: 'Phones' },
  { name: 'កុំព្យូទ័រ', nameEn: 'Computers' },
  { name: 'គ្រឿងសង្ហារឹម', nameEn: 'Furniture' },
  { name: 'សម្លៀកបំពាក់', nameEn: 'Clothing' },
  { name: 'កីឡា', nameEn: 'Sports' },
  { name: 'ការងារ', nameEn: 'Jobs' },
  { name: 'សេវាកម្ម', nameEn: 'Services' },
  { name: 'អាជីវកម្ម', nameEn: 'Business' },
  { name: 'សត្វចិញ្ចឹម', nameEn: 'Pets' },
];

async function main() {
  const { count } = parseArgs();

  console.log(chalk.bold.green('\nStarting category seeder...'));
  console.log(chalk.bold('Categories to create:'), chalk.cyan(count === 0 ? 'all (' + categories.length + ')' : count + '\n'));

  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'mysql_db',
    user: process.env.DB_USER || 'app_user',
    password: process.env.DB_PASSWORD || 'secret',
    database: process.env.DB_NAME || 'app_db',
    port: parseInt(process.env.DB_PORT || '3306'),
  });

  try {
    // Get existing categories
    const [existing] = await connection.query('SELECT id, name FROM category');
    const existingNames = (existing as Array<{ name: string }>).map(c => c.name);

    console.log(chalk.bold('Found'), chalk.green(existingNames.length), chalk.bold('existing categories') + '\n');

    let created = 0;
    let skipped = 0;

    console.log(chalk.bold.blue('Creating categories...\n'));

    for (const cat of categories) {
      // Stop if we've created the requested amount
      if (count > 0 && created >= count) break;

      if (existingNames.includes(cat.name)) {
        console.log(chalk.dim(`  [skip] ${cat.name} (${cat.nameEn})`));
        skipped++;
        continue;
      }

      // Use category placeholder image
      const imageName = 'categories/default.jpg';

      await connection.query(
        'INSERT INTO category (name, category_image) VALUES (?, ?)',
        [cat.name, imageName]
      );

      console.log(chalk.dim(`  [${created + 1}]`), chalk.bold(cat.name), chalk.dim(`(${cat.nameEn})`));
      created++;
    }

    console.log(chalk.bold.green('\nCategory seeding complete'));
    console.log(chalk.bold('Created:'), chalk.green(created));
    console.log(chalk.bold('Skipped:'), chalk.yellow(skipped));
    const total = count > 0 ? Math.min(count, categories.length) : categories.length;
    console.log(chalk.bold('Total available:'), chalk.cyan(categories.length) + '\n');

  } catch (error) {
    console.error(chalk.red('Error during category seeding:'), error);
    process.exit(1);
  } finally {
    await connection.end();
  }
}

main();
