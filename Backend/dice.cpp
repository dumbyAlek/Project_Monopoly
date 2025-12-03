//dice.cpp
#include <iostream>
#include <random>

int main() {
    std::random_device rd;
    std::mt19937 gen(rd());
    std::uniform_int_distribution<> dice(1,6);

    int die1 = dice(gen);
    int die2 = dice(gen);

    std::cout << "{\"die1\":" << die1 << ",\"die2\":" << die2 << "}" << std::endl;

    return 0;
}
