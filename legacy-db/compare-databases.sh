#!/bin/bash
# ==========================================
# COMPARISON SCRIPT: Legacy vs New Database
# ==========================================
# Usage: ./compare-databases.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  DATABASE COMPARISON: Legacy vs New    ${NC}"
echo -e "${BLUE}========================================${NC}"

# Legacy database connection
LEGACY_HOST="localhost"
LEGACY_PORT="5433"
LEGACY_DB="legacy_hospital"
LEGACY_USER="legacy_user"
LEGACY_PASS="legacy_password"

# New database connection
NEW_HOST="localhost"
NEW_PORT="5432"
NEW_DB="hospital_db"
NEW_USER="hospital_user"
NEW_PASS="password"

echo -e "\n${YELLOW}1. Checking if databases are accessible...${NC}"

# Check legacy database
if PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -c "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Legacy database is accessible${NC}"
else
    echo -e "${RED}✗ Legacy database is not accessible${NC}"
    echo "  Start with: cd legacy-db && docker compose up -d"
    exit 1
fi

# Check new database
if PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -c "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ New database is accessible${NC}"
else
    echo -e "${RED}✗ New database is not accessible${NC}"
    echo "  Start with: docker compose up -d"
    exit 1
fi

echo -e "\n${YELLOW}2. Comparing tables...${NC}"

# Legacy tables
echo -e "${BLUE}Legacy tables:${NC}"
PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -c "
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
"

# New tables
echo -e "${BLUE}New tables:${NC}"
PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -c "
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
"

echo -e "\n${YELLOW}3. Row counts comparison...${NC}"

# Get legacy row counts
echo -e "${BLUE}Legacy row counts:${NC}"
PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -c "
SELECT 
    schemaname,
    relname as table_name,
    n_live_tup as row_count
FROM pg_stat_user_tables
ORDER BY relname;
"

# Get new row counts
echo -e "${BLUE}New row counts:${NC}"
PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -c "
SELECT 
    schemaname,
    relname as table_name,
    n_live_tup as row_count
FROM pg_stat_user_tables
ORDER BY relname;
"

echo -e "\n${YELLOW}4. Column comparison for common tables...${NC}"

# Compare client table if exists in both
for TABLE in client facture fournis banque bordereau reglement; do
    echo -e "${BLUE}Table: $TABLE${NC}"
    
    # Legacy columns
    echo -e "  ${GREEN}Legacy columns:${NC}"
    PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -t -c "
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name = '$TABLE' 
    ORDER BY ordinal_position;
    " 2>/dev/null || echo "    (table not found)"
    
    # New columns
    echo -e "  ${YELLOW}New columns:${NC}"
    PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -t -c "
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name = '$TABLE' 
    ORDER BY ordinal_position;
    " 2>/dev/null || echo "    (table not found)"
    echo ""
done

echo -e "\n${YELLOW}5. Index comparison...${NC}"

# Legacy indexes
echo -e "${BLUE}Legacy indexes:${NC}"
PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -c "
SELECT 
    indexname,
    tablename
FROM pg_indexes 
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
"

# New indexes
echo -e "${BLUE}New indexes:${NC}"
PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -c "
SELECT 
    indexname,
    tablename
FROM pg_indexes 
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
"

echo -e "\n${YELLOW}6. Functions comparison...${NC}"

# Legacy functions
echo -e "${BLUE}Legacy functions:${NC}"
PGPASSWORD=$LEGACY_PASS psql -h $LEGACY_HOST -p $LEGACY_PORT -U $LEGACY_USER -d $LEGACY_DB -c "
SELECT 
    routine_name,
    routine_type
FROM information_schema.routines
WHERE routine_schema = 'public'
ORDER BY routine_name;
"

# New functions
echo -e "${BLUE}New functions:${NC}"
PGPASSWORD=$NEW_PASS psql -h $NEW_HOST -p $NEW_PORT -U $NEW_USER -d $NEW_DB -c "
SELECT 
    routine_name,
    routine_type
FROM information_schema.routines
WHERE routine_schema = 'public'
ORDER BY routine_name;
"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  Comparison complete!                   ${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "\n${YELLOW}Quick access:${NC}"
echo "  Legacy Adminer: http://localhost:37800"
echo "  Legacy API:     http://localhost:3001"
echo "  Legacy Swagger: http://localhost:37801"
echo "  New App:        http://localhost:9090"
