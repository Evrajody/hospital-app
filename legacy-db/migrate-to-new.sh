#!/bin/bash
# ==========================================
# MIGRATION SCRIPT: Legacy to New Database
# ==========================================
# This script helps migrate data from legacy to new database

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  MIGRATION: Legacy → New Database      ${NC}"
echo -e "${BLUE}========================================${NC}"

# Database connections
LEGACY_HOST="localhost"
LEGACY_PORT="5433"
LEGACY_DB="legacy_hospital"
LEGACY_USER="legacy_user"
LEGACY_PASS="legacy_password"

NEW_HOST="localhost"
NEW_PORT="5432"
NEW_DB="hospital_db"
NEW_USER="hospital_user"
NEW_PASS="password"

# Export directory
EXPORT_DIR="/tmp/legacy_export_$(date +%Y%m%d_%H%M%S)"
mkdir -p $EXPORT_DIR

echo -e "\n${YELLOW}Step 1: Exporting data from legacy database...${NC}"

# Export tables
TABLES=("client" "facture" "fournis" "facfournis" "banque" "bordereau" "reglement" "plan_comptable_ohada")

for TABLE in "${TABLES[@]}"; do
    echo -e "  ${BLUE}Exporting $TABLE...${NC}"
    PGPASSWORD=$LEGACY_PASS pg_dump -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB \
        -t public.$TABLE \
        --data-only \
        --column-inserts \
        > "$EXPORT_DIR/$TABLE.sql" 2>/dev/null || echo "    (table not found or empty)"
done

echo -e "\n${YELLOW}Step 2: Transforming data for new schema...${NC}"

# Here you would add transformations based on your new schema
# This is a placeholder for custom migration logic

echo -e "${GREEN}Exports saved to: $EXPORT_DIR${NC}"

echo -e "\n${YELLOW}Step 3: Migration summary...${NC}"

# Count exported rows
for TABLE in "${TABLES[@]}"; do
    if [ -f "$EXPORT_DIR/$TABLE.sql" ]; then
        ROWS=$(grep -c "^INSERT" "$EXPORT_DIR/$TABLE.sql" 2>/dev/null || echo "0")
        echo -e "  ${GREEN}$TABLE: $ROWS inserts${NC}"
    fi
done

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  Export complete!                       ${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "\n${YELLOW}Next steps:${NC}"
echo "1. Review exported SQL files in: $EXPORT_DIR"
echo "2. Adapt to your new schema structure"
echo "3. Run imports into new database"
echo ""
echo "Example import:"
echo "  PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -f $EXPORT_DIR/client.sql"
